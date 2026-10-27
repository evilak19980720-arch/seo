<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('front_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('配置项');
            $table->text('value')->nullable()->comment('值');
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->engine = 'InnoDB';
            $table->comment('前台全局配置');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_settings');
    }
};
