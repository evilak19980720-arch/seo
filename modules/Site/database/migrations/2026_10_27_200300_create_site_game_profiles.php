<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('site_game_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->comment('站点');
            $table->string('genre', 100)->default('')->comment('游戏类型');
            $table->text('summary')->nullable()->comment('简介');
            $table->text('gameplay')->nullable()->comment('玩法');
            $table->json('features')->nullable()->comment('特色列表');
            $table->json('faq')->nullable()->comment('常见问题 [{q,a}]');
            $table->json('store_links')->nullable()->comment('商店链接 {google_play, app_store, web}');
            $table->json('screenshots')->nullable()->comment('截图地址列表');
            $table->string('video_url', 500)->default('')->comment('宣传视频地址');
            $table->unsignedTinyInteger('status')->default(1)->comment('1 生成中 2 待确认 3 已确认 4 生成失败');
            $table->string('error', 1000)->default('')->comment('生成失败原因');
            $table->unsignedInteger('confirmed_at')->default(0)->comment('确认时间');
            $table->unsignedInteger('confirmed_by')->default(0)->comment('确认人');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->unique(['site_id', 'deleted_at']);
            $table->index(['status', 'deleted_at']);
            $table->engine = 'InnoDB';
            $table->comment('游戏资料');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_game_profiles');
    }
};
