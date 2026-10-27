<?php

return [
    // 后台上传的图片（Logo、截图、封面）所在域名，留空使用 APP_URL
    'asset_url' => env('FRONT_ASSET_URL', ''),

    // 本机 HTML 缓存秒数（发布、修改内容时会自动失效）
    'page_ttl' => (int) env('FRONT_PAGE_TTL', 86400),

    // 返回给 Cloudflare 的边缘缓存秒数（需要在 Cloudflare 配置缓存 HTML 的规则才生效）
    'edge_ttl' => (int) env('FRONT_EDGE_TTL', 3600),

    // 文章列表每页数量
    'per_page' => 12,
];
