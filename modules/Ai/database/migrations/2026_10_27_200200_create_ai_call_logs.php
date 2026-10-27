<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('ai_call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->default(0)->comment('渠道');
            $table->unsignedBigInteger('prompt_id')->default(0)->comment('提示词');
            $table->unsignedInteger('prompt_version')->default(0)->comment('提示词版本');
            $table->unsignedTinyInteger('type')->default(0)->comment('提示词类型');
            $table->unsignedBigInteger('site_id')->default(0)->comment('站点');
            $table->string('model', 100)->default('')->comment('模型');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('duration_ms')->default(0)->comment('耗时毫秒');
            $table->unsignedTinyInteger('status')->default(1)->comment('1 成功 2 失败');
            $table->string('error', 1000)->default('')->comment('错误信息');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index(['site_id', 'created_at']);
            $table->index(['channel_id', 'created_at']);
            $table->engine = 'InnoDB';
            $table->comment('AI 调用日志');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_call_logs');
    }
};
