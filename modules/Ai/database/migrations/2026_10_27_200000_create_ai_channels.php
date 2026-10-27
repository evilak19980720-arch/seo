<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('ai_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('渠道名称');
            $table->unsignedTinyInteger('provider')->default(1)->comment('协议 1 OpenAI 兼容 2 Anthropic');
            $table->string('base_url', 255)->comment('接口地址');
            $table->text('api_key')->comment('API Key（加密存储）');
            $table->string('model', 100)->comment('模型名称');
            $table->decimal('temperature', 3, 2)->default(0.7)->comment('温度');
            $table->unsignedInteger('max_tokens')->default(4096)->comment('最大输出 token');
            $table->unsignedInteger('timeout')->default(120)->comment('超时秒数');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 启用 2 禁用');
            $table->string('remark', 500)->default('')->comment('备注');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index(['status', 'deleted_at']);
            $table->engine = 'InnoDB';
            $table->comment('AI 渠道');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_channels');
    }
};
