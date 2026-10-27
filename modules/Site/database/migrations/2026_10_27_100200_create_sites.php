<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('网站名称（游戏品牌名）');
            $table->string('domain', 191)->comment('主域名，不含 www');
            $table->string('locale', 10)->default('pt-BR')->comment('语言代码');
            $table->string('title', 191)->default('')->comment('首页标题');
            $table->string('description', 500)->default('')->comment('首页描述');
            $table->string('keywords', 500)->default('')->comment('关键词');
            $table->string('logo', 500)->default('')->comment('Logo 地址');
            $table->string('game_brief', 1000)->default('')->comment('游戏说明，供 AI 生成资料参考');
            $table->unsignedBigInteger('server_id')->default(0)->comment('所在服务器');
            $table->unsignedBigInteger('cloudflare_account_id')->default(0)->comment('所属 Cloudflare 账号');
            $table->string('zone_id', 64)->default('')->comment('Cloudflare Zone ID');
            $table->json('name_servers')->nullable()->comment('Cloudflare 分配的 NS');
            $table->unsignedTinyInteger('onboard_status')->default(1)->comment('接入状态 1 待接入 2 等待 NS 生效 3 解析已写入 4 已上线 5 失败');
            $table->string('onboard_error', 1000)->default('')->comment('接入失败原因');
            $table->unsignedInteger('online_at')->default(0)->comment('上线时间');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 启用 2 禁用');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->unique(['domain', 'deleted_at']);
            $table->index(['server_id', 'deleted_at']);
            $table->index(['onboard_status', 'deleted_at']);
            $table->engine = 'InnoDB';
            $table->comment('站点');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
