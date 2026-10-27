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
                'permission_name' => '内容管理',
                'route' => '/content',
                'icon' => 'document-text',
                'permission_mark' => '',
                'component' => '/layout/index.vue',
                'type' => 1,
                'sort' => 3,
            ], [
                $this->menu('文章', 'articles', 'articles', '/content/articles/index.vue', 1, ['publish' => '发布', 'unpublish' => '下线', 'rewrite' => '重写'], ['index' => '列表', 'store' => '新增', 'show' => '读取', 'update' => '更新', 'destroy' => '删除']),
                $this->menu('选题', 'topics', 'topics', '/content/topics/index.vue', 2, ['generate' => 'AI 生成选题', 'write' => '生成文章'], ['index' => '列表', 'store' => '新增', 'show' => '读取', 'update' => '更新', 'destroy' => '删除']),
                $this->menu('生产配置', 'settings', 'settings', '/content/settings/index.vue', 3, [], ['index' => '读取', 'store' => '保存']),
            ]),
        ];
    }

    /**
     * @param  array<string, string>  $extraActions
     * @param  array<string, string>|null  $actions
     */
    protected function menu(string $name, string $route, string $controller, string $component, int $sort, array $extraActions = [], ?array $actions = null, bool $hidden = false): array
    {
        $actions = ($actions ?? ['index' => '列表', 'store' => '新增', 'show' => '读取', 'update' => '更新', 'destroy' => '删除', 'enable' => '禁用/启用']) + $extraActions;

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
            // 只用于分配按钮权限的菜单不在侧边栏显示
            'hidden' => $hidden ? 2 : 1,
        ], $buttons);
    }

    protected function node(array $attributes, array $children = []): array
    {
        $node = $attributes + [
            'parent_id' => 0,
            'icon' => '',
            'module' => 'content',
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
