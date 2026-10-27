<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('content_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->comment('站点');
            $table->string('title', 255)->comment('选题标题');
            $table->string('keyword', 191)->default('')->comment('目标关键词');
            $table->string('intent', 30)->default('')->comment('搜索意图 guide/explanation/tips/faq/news');
            $table->unsignedTinyInteger('status')->default(1)->comment('1 待写 2 写作中 3 已成文 4 放弃');
            $table->unsignedTinyInteger('source')->default(1)->comment('1 AI 2 人工');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index(['site_id', 'status', 'deleted_at']);
            $table->engine = 'InnoDB';
            $table->comment('文章选题');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_topics');
    }
};
