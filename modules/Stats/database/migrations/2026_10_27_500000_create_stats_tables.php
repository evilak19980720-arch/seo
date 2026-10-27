<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('stats_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('date')->comment('日期 Ymd');
            $table->unsignedBigInteger('site_id')->default(0)->comment('站点');
            $table->string('metric', 30)->comment('指标');
            $table->unsignedBigInteger('value')->default(0)->comment('数值');

            $table->unique(['date', 'site_id', 'metric']);
            $table->index(['metric', 'date']);
            $table->engine = 'InnoDB';
            $table->comment('每日统计');
        });

        Schema::create('stats_crawls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->comment('站点');
            $table->string('bot', 20)->comment('google / bing');
            $table->string('path', 500)->comment('抓取路径');
            $table->unsignedSmallInteger('status')->comment('HTTP 状态码');
            $table->string('ip', 45)->comment('IP');
            $table->unsignedInteger('created_at')->comment('抓取时间');

            $table->index(['site_id', 'created_at']);
            $table->index(['created_at']);
            $table->engine = 'InnoDB';
            $table->comment('蜘蛛抓取记录（保留 30 天）');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats_daily');
        Schema::dropIfExists('stats_crawls');
    }
};
