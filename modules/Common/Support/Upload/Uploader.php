<?php

namespace Modules\Common\Support\Upload;

use Catch\Exceptions\FailedException;
use Illuminate\Http\UploadedFile;
use Modules\Common\Events\UploadedEvent;
use Modules\Common\Support\Upload\Uses\ChunkUpload;
use Modules\Common\Support\Upload\Uses\CloudUpload;
use Modules\Common\Support\Upload\Uses\LocalUpload;

class Uploader
{
    protected string $driver = 'local';

    protected int $categoryId = 0;

    /**
     * 上传类型 image|all
     */
    protected string $type = 'all';

    /**
     * path
     */
    protected string $path = '';

    /**
     * upload
     */
    public function upload(UploadedFile $file): array
    {
        try {
            $uploadInfo = $this->getDriver()->setUploadedFile($file)->setType($this->type)->upload();
            // 附件分类ID
            $uploadInfo['category_id'] = $this->categoryId;
            UploadedEvent::dispatch($uploadInfo);

            return $uploadInfo;
        } catch (\Exception $exception) {
            throw new FailedException($exception->getMessage());
        }
    }

    /**
     *  get driver
     */
    public function getDriver()
    {
        // 上传配置里选了 S3 或阿里云 OSS 时，服务端直接上传到对象存储
        $configured = (string) config('upload.driver');
        if ($this->driver === 'local' && in_array($configured, ['s3', 'oss'], true)) {
            return new CloudUpload($configured);
        }

        $drivers = $this->getDrivers();

        $driver = $drivers[$this->driver] ?? null;

        if (! $driver) {
            throw new FailedException('Upload Driver Not Found');
        }

        return app($driver);
    }

    /**
     * set driver
     *
     * @return $this
     */
    public function setDriver(string $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return $this
     */
    public function withCategoryId(mixed $categoryId): static
    {
        $this->categoryId = intval($categoryId);

        return $this;
    }

    /**
     * 设置上传类型，用于限制扩展名白名单范围
     *
     * @return $this
     */
    public function withType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * get drivers
     *
     * @return string[]
     */
    public function getDrivers(): array
    {
        return [
            'local' => LocalUpload::class,
            'chunk' => ChunkUpload::class,
        ];
    }
}
