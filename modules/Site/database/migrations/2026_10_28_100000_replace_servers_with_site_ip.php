<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 去掉服务器管理：站点直接保存解析 IP
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('ip', 45)->default('')->after('cloudflare_account_id')->comment('解析 IP');
        });

        if (Schema::hasTable('site_servers')) {
            foreach (DB::table('site_servers')->get(['id', 'ip']) as $server) {
                DB::table('sites')->where('server_id', $server->id)->update(['ip' => $server->ip]);
            }
        }

        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex(['server_id', 'deleted_at']);
        });
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('server_id');
        });

        Schema::dropIfExists('site_servers');

        // 删除「服务器」菜单和按钮权限
        if (Schema::hasTable('permissions')) {
            $menuIds = DB::table('permissions')->where('module', 'site')->where('permission_mark', 'servers')->pluck('id');
            DB::table('permissions')->whereIn('parent_id', $menuIds)->delete();
            DB::table('permissions')->whereIn('id', $menuIds)->delete();
        }
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->default(0)->comment('所在服务器');
            $table->dropColumn('ip');
        });
    }
};
