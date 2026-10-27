<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('site_cloudflare_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('账号名称');
            $table->string('account_id', 64)->comment('Cloudflare Account ID');
            $table->text('api_token')->comment('API Token（加密存储）');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 启用 2 禁用');
            $table->string('remark', 500)->default('')->comment('备注');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index(['status', 'deleted_at']);
            $table->engine = 'InnoDB';
            $table->comment('Cloudflare 账号');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_cloudflare_accounts');
    }
};
