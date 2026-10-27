<?php

declare(strict_types=1);

use Illuminate\Database\Seeder;
use Modules\Common\Support\ImportPermissions;

return new class extends Seeder
{
    public function run(): void
    {
        ImportPermissions::import($this->menus());
    }

    public function menus(): array
    {
        return [
            $this->node([
                'permission_name' => '站点管理',
                'route' => '/site',
                'icon' => 'globe-alt',
                'permission_mark' => '',
                'component' => '/layout/index.vue',
                'type' => 1,
                'sort' => 2,
            ], [
                $this->menu('站点列表', 'sites', 'sites', '/site/sites/index.vue', 1, ['onboard' => '重新接入']),
                $this->menu('服务器', 'servers', 'servers', '/site/servers/index.vue', 2),
                $this->menu('Cloudflare 账号', 'cloudflare', 'cloudflareAccounts', '/site/cloudflare/index.vue', 3, ['verify' => '校验 Token']),
            ]),
        ];
    }

    /**
     * @param  array<string, string>  $extraActions
     */
    protected function menu(string $name, string $route, string $controller, string $component, int $sort, array $extraActions = []): array
    {
        $actions = ['index' => '列表', 'store' => '新增', 'show' => '读取', 'update' => '更新', 'destroy' => '删除', 'enable' => '禁用/启用'] + $extraActions;

        $buttons = [];
        $buttonSort = 1;
        foreach ($actions as $action => $label) {
            $buttons[] = $this->node([
                'permission_name' => $label,
                'route' => '',
                'permission_mark' => "{$controller}@{$action}",
                'component' => '',
                'type' => 3,
                'sort' => $buttonSort++,
            ]);
        }

        return $this->node([
            'permission_name' => $name,
            'route' => $route,
            'permission_mark' => $controller,
            'component' => $component,
            'type' => 2,
            'sort' => $sort,
        ], $buttons);
    }

    protected function node(array $attributes, array $children = []): array
    {
        $node = $attributes + [
            'parent_id' => 0,
            'icon' => '',
            'module' => 'site',
            'redirect' => '',
            'keepalive' => 1,
            'hidden' => 1,
            'active_menu' => '',
            'creator_id' => 1,
        ];

        if ($children) {
            $node['children'] = $children;
        }

        return $node;
    }
};
