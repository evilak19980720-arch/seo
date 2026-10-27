<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('提示词名称');
            $table->unsignedTinyInteger('type')->comment('类型 1 游戏资料 2 选题 3 生成文章 4 润色 5 审核 6 标题描述');
            $table->unsignedBigInteger('channel_id')->default(0)->comment('使用的 AI 渠道');
            $table->text('system_prompt')->nullable()->comment('系统提示词');
            $table->text('content')->comment('用户提示词，支持 {变量}');
            $table->unsignedInteger('version')->default(1)->comment('版本号，每次修改内容自动 +1');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 启用 2 禁用');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index(['type', 'status', 'deleted_at']);
            $table->engine = 'InnoDB';
            $table->comment('AI 提示词');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
    }
};
