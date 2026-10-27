<?php

declare(strict_types=1);

namespace Modules\Common\Support\Upload\Uses;

use Modules\Common\Support\Upload\Cloud\ObjectStorage;

/**
 * 上传到对象存储（S3 / 阿里云 OSS），path 返回完整访问地址
 */
class CloudUpload extends Upload
{
    public function __construct(
        protected string $driver = 's3'
    ) {
    }

    public function upload(): array
    {
        $this->dealBeforeUpload();

        $key = date('Y-m-d').'/attachments/'.$this->generateName($this->getUploadedFileExt());
        $mime = $this->file->getMimeType() ?: 'application/octet-stream';

        return [
            'path' => ObjectStorage::put($this->driver, $key, $this->file->getContent(), $mime),
            'ext' => $this->getUploadedFileExt(),
            'type' => $this->getUploadedFileMimeType(),
            'size' => $this->getUploadedFileSize(),
            'original_name' => $this->getOriginName(),
            'driver' => $this->driver,
        ];
    }
}
