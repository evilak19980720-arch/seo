<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('广告名称');
            $table->string('position', 30)->comment('广告位');
            $table->unsignedTinyInteger('type')->default(1)->comment('1 图片 2 代码');
            $table->string('image', 500)->default('')->comment('图片');
            $table->unsignedSmallInteger('width')->default(0)->comment('图片宽');
            $table->unsignedSmallInteger('height')->default(0)->comment('图片高');
            $table->string('link', 500)->default('')->comment('跳转地址');
            $table->string('alt', 191)->default('')->comment('图片说明');
            $table->text('html')->nullable()->comment('广告代码');
            $table->json('site_ids')->nullable()->comment('投放站点，空为全部');
            $table->unsignedSmallInteger('weight')->default(1)->comment('权重');
            $table->unsignedInteger('start_at')->default(0)->comment('开始时间');
            $table->unsignedInteger('end_at')->default(0)->comment('结束时间，0 不限');
            $table->unsignedInteger('clicks')->default(0)->comment('点击数');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 启用 2 禁用');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index(['position', 'status', 'deleted_at']);
            $table->engine = 'InnoDB';
            $table->comment('广告');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
