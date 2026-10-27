# 前台站点（Front）

同一套 Laravel 代码按访问域名渲染每个游戏官网（pt-BR）。

## 路由
| 路径 | 说明 |
|---|---|
| `/` | 官网首页：游戏简介、商店按钮、截图、预告片、玩法、亮点、最新文章、FAQ |
| `/artigos` | 文章列表（`?page=n`） |
| `/artigos/{slug}` | 文章页；已下线或已删除返回 410 |
| `/sitemap.xml`、`/robots.txt` | 每个站点独立生成 |

`www.` 访问 301 到根域名；不认识的域名返回 404；后台域名（APP_URL）的 `/` 显示 welcome 页面。

## SEO
- title、description、canonical、Open Graph（`og:locale=pt_BR`）、Twitter Card。
- JSON-LD：首页 `WebSite` + `VideoGame`，文章页 `Article` + `BreadcrumbList`。
- 游戏资料未确认或域名未上线时，所有页面 `noindex`（meta + `X-Robots-Tag`），sitemap 为空。

## 缓存
- 本机：页面 HTML 存入 Cache（生产用 Redis），每站一个版本号；文章发布/下线、站点或游戏资料修改时整站失效。
- Cloudflare：响应带 `Cache-Control: s-maxage`（`FRONT_EDGE_TTL`，默认 3600 秒）。要让 Cloudflare 缓存 HTML，需在 Cloudflare 添加缓存规则「Cache Everything / Eligible for cache」。文章发布/下线时会调用 Cloudflare 按 URL 清缓存，所以 API Token 需要 **Zone → Cache Purge** 权限。

## 配置（.env）
- `FRONT_ASSET_URL`：后台上传图片所在域名（渲染节点与后台分开部署时必须设置），默认 APP_URL。
- `FRONT_PAGE_TTL`、`FRONT_EDGE_TTL`。

## Nginx
渲染节点的 server 块用 `server_name _;`（或泛域名）把所有站点域名交给同一个 PHP 入口即可。

## 广告位
模板里预留了 `header`、`footer`、`home_middle`、`article_top`、`article_bottom` 位置，由广告模块填充，没有广告时不输出任何内容。
