<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('content_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('配置键');
            $table->string('value', 1000)->default('')->comment('配置值');
            $table->createdAt();
            $table->updatedAt();
            $table->engine = 'InnoDB';
            $table->comment('内容生产配置');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_settings');
    }
};
