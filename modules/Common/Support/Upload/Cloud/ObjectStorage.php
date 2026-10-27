<?php

declare(strict_types=1);

namespace Modules\Common\Support\Upload\Cloud;

use Catch\Exceptions\FailedException;
use Illuminate\Support\Facades\Http;

/**
 * 服务端直传对象存储（亚马逊 S3 / 阿里云 OSS），不依赖 SDK
 */
class ObjectStorage
{
    /**
     * 上传后返回公开访问地址
     */
    public static function put(string $driver, string $key, string $content, string $mime): string
    {
        $config = (array) config("upload.{$driver}", []);
        foreach (['access_key', 'secret_key', 'bucket'] as $required) {
            if (empty($config[$required])) {
                throw new FailedException("请先在「系统管理 → 上传配置」里填写 {$driver} 的 {$required}");
            }
        }

        $key = trim(trim((string) ($config['prefix'] ?? ''), '/').'/'.ltrim($key, '/'), '/');

        return match ($driver) {
            's3' => static::s3($config, $key, $content, $mime),
            'oss' => static::oss($config, $key, $content, $mime),
            default => throw new FailedException('不支持的存储驱动'),
        };
    }

    /**
     * AWS Signature V4，兼容 S3 协议的存储（R2、MinIO 等）可以填写 endpoint
     */
    protected static function s3(array $config, string $key, string $content, string $mime): string
    {
        $region = $config['region'] ?: 'us-east-1';
        $bucket = $config['bucket'];
        $endpoint = rtrim((string) ($config['endpoint'] ?? ''), '/');
        $path = '/'.implode('/', array_map('rawurlencode', explode('/', $key)));

        if ($endpoint) {
            // 自定义 endpoint 使用 path-style：https://endpoint/bucket/key
            $host = (string) parse_url($endpoint, PHP_URL_HOST);
            $scheme = parse_url($endpoint, PHP_URL_SCHEME) ?: 'https';
            $path = '/'.rawurlencode($bucket).$path;
            $url = "{$scheme}://{$host}{$path}";
        } else {
            $host = "{$bucket}.s3.{$region}.amazonaws.com";
            $url = "https://{$host}{$path}";
        }

        $time = time();
        $amzDate = gmdate('Ymd\THis\Z', $time);
        $date = gmdate('Ymd', $time);
        $hash = hash('sha256', $content);
        $headers = ['content-type' => $mime, 'host' => $host, 'x-amz-content-sha256' => $hash, 'x-amz-date' => $amzDate];
        ksort($headers);

        $canonicalHeaders = implode('', array_map(fn ($name, $value) => "{$name}:{$value}\n", array_keys($headers), $headers));
        $signedHeaders = implode(';', array_keys($headers));
        $canonical = "PUT\n{$path}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$hash}";
        $scope = "{$date}/{$region}/s3/aws4_request";
        $toSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n".hash('sha256', $canonical);

        $signingKey = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', 's3', hash_hmac('sha256', $region, hash_hmac('sha256', $date, 'AWS4'.$config['secret_key'], true), true), true), true);
        $signature = hash_hmac('sha256', $toSign, $signingKey);

        $response = Http::withHeaders([
            'Content-Type' => $mime,
            'x-amz-content-sha256' => $hash,
            'x-amz-date' => $amzDate,
            'Authorization' => "AWS4-HMAC-SHA256 Credential={$config['access_key']}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}",
        ])->withBody($content, $mime)->timeout(60)->put($url);

        if (! $response->successful()) {
            throw new FailedException('S3 上传失败：HTTP '.$response->status().' '.mb_substr(strip_tags($response->body()), 0, 200));
        }

        return static::publicUrl($config, $url, $key);
    }

    /**
     * 阿里云 OSS 签名（Header 方式 V1）
     */
    protected static function oss(array $config, string $key, string $content, string $mime): string
    {
        $endpoint = preg_replace('#^https?://#', '', rtrim((string) ($config['end_point'] ?? ''), '/'));
        if (! $endpoint) {
            throw new FailedException('请先在「系统管理 → 上传配置」里填写阿里云 OSS 的 end_point，例如 oss-cn-hongkong.aliyuncs.com');
        }

        $bucket = $config['bucket'];
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $toSign = "PUT\n\n{$mime}\n{$date}\n/{$bucket}/{$key}";
        $signature = base64_encode(hash_hmac('sha1', $toSign, $config['secret_key'], true));
        $url = "https://{$bucket}.{$endpoint}/".implode('/', array_map('rawurlencode', explode('/', $key)));

        $response = Http::withHeaders([
            'Content-Type' => $mime,
            'Date' => $date,
            'Authorization' => "OSS {$config['access_key']}:{$signature}",
        ])->withBody($content, $mime)->timeout(60)->put($url);

        if (! $response->successful()) {
            throw new FailedException('OSS 上传失败：HTTP '.$response->status().' '.mb_substr(strip_tags($response->body()), 0, 200));
        }

        return static::publicUrl($config, $url, $key);
    }

    /**
     * 配置了访问域名（CDN）时返回该域名下的地址
     */
    protected static function publicUrl(array $config, string $url, string $key): string
    {
        $domain = rtrim((string) ($config['domain'] ?? ''), '/');
        if ($domain === '') {
            return $url;
        }
        if (! preg_match('#^https?://#', $domain)) {
            $domain = 'https://'.$domain;
        }

        return $domain.'/'.implode('/', array_map('rawurlencode', explode('/', $key)));
    }
}
