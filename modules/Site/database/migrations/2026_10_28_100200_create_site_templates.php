<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('site_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->unique()->comment('站点');
            $table->string('reference_image', 500)->default('')->comment('参考图');
            $table->string('style_notes', 1000)->default('')->comment('风格说明');
            $table->longText('css')->nullable()->comment('草稿 CSS');
            $table->text('header_html')->nullable()->comment('草稿页头');
            $table->text('footer_html')->nullable()->comment('草稿页脚');
            $table->longText('published_css')->nullable()->comment('已启用 CSS');
            $table->text('published_header')->nullable()->comment('已启用页头');
            $table->text('published_footer')->nullable()->comment('已启用页脚');
            $table->unsignedTinyInteger('status')->default(0)->comment('0 未生成 1 生成中 2 待确认 3 已启用 4 生成失败');
            $table->string('error', 1000)->default('')->comment('失败原因');
            $table->unsignedInteger('published_at')->default(0)->comment('启用时间');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();
            $table->engine = 'InnoDB';
            $table->comment('站点模板（AI 生成）');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_templates');
    }
};
