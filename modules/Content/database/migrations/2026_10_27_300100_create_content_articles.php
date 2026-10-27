<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('content_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->comment('站点');
            $table->unsignedBigInteger('topic_id')->default(0)->comment('选题');
            $table->string('title', 255)->default('')->comment('标题');
            $table->string('slug', 191)->default('')->comment('URL 路径');
            $table->string('keyword', 191)->default('')->comment('目标关键词');
            $table->string('meta_title', 255)->default('')->comment('SEO 标题');
            $table->string('meta_description', 500)->default('')->comment('SEO 描述');
            $table->string('cover', 500)->default('')->comment('封面图');
            $table->longText('content')->nullable()->comment('正文 Markdown');
            $table->unsignedInteger('word_count')->default(0)->comment('字数');
            $table->unsignedTinyInteger('status')->default(1)->comment('1 生成中 2 审核未通过 3 待发布 4 已发布 5 已下线 6 生成失败');
            $table->unsignedTinyInteger('review_score')->default(0)->comment('AI 审核最低分');
            $table->json('review_detail')->nullable()->comment('审核明细');
            $table->unsignedTinyInteger('attempts')->default(0)->comment('生成次数');
            $table->string('error', 1000)->default('')->comment('失败原因');
            $table->unsignedTinyInteger('source')->default(1)->comment('1 AI 2 人工');
            $table->unsignedInteger('published_at')->default(0)->comment('发布时间');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index(['site_id', 'status', 'deleted_at']);
            $table->index(['site_id', 'slug']);
            $table->index(['status', 'published_at']);
            $table->engine = 'InnoDB';
            $table->comment('文章');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_articles');
    }
};
