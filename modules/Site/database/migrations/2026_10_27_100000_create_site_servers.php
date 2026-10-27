<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('site_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('服务器名称');
            $table->string('ip', 45)->comment('公网 IP，写入 Cloudflare DNS');
            $table->string('region', 50)->default('')->comment('区域，例如 sa-east-1');
            $table->unsignedInteger('capacity')->default(100)->comment('最多承载站点数');
            $table->unsignedInteger('site_count')->default(0)->comment('已分配站点数');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 启用 2 禁用');
            $table->string('remark', 500)->default('')->comment('备注');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->unique(['ip', 'deleted_at']);
            $table->index(['status', 'deleted_at']);
            $table->engine = 'InnoDB';
            $table->comment('前台渲染服务器');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_servers');
    }
};
