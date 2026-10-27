<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('site_dns_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('batch', 32)->comment('批次');
            $table->unsignedBigInteger('cloudflare_account_id')->comment('Cloudflare 账号');
            $table->string('zone_id', 64)->comment('Zone ID');
            $table->string('domain', 191)->comment('域名');
            $table->string('ip', 45)->comment('解析 IP');
            $table->boolean('proxied')->default(true)->comment('是否开启 Cloudflare 代理');
            $table->boolean('with_www')->default(true)->comment('同时解析 www');
            $table->unsignedTinyInteger('status')->default(1)->comment('1 等待 2 成功 3 失败');
            $table->string('error', 1000)->default('')->comment('失败原因');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index(['batch']);
            $table->index(['domain']);
            $table->engine = 'InnoDB';
            $table->comment('批量解析任务');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_dns_tasks');
    }
};
