# 数据统计（Stats）

| 指标 | 来源 | 更新 |
|---|---|---|
| Googlebot / Bingbot 抓取 | 前台请求，UA 匹配后用反向 + 正向 DNS 验证 IP（排除伪装的 UA） | 实时 |
| 页面浏览、独立访客、请求数 | Cloudflare GraphQL Analytics（`httpRequests1dGroups`） | 每天 03:10 拉前一天（`stats:cloudflare`） |
| 广告点击 | `/go/{id}` 跳转 | 实时 |
| 发布文章、AI 调用 | 文章表、AI 调用日志 | 实时 |

注意：
- 被 Cloudflare 边缘缓存命中的请求不会到达服务器，所以「蜘蛛抓取」是到达源站的抓取次数，偏低于真实值。真实收录与点击请以 Google Search Console 为准（后续可接 Search Console API）。
- 源站要拿到真实访客 IP 依赖 `CF-Connecting-IP`，建议服务器防火墙只放行 Cloudflare 的 IP 段，否则该请求头可以被伪造。
- Cloudflare API Token 需要额外的 **Account Analytics / Zone Analytics: Read** 权限。
- 抓取明细保留 30 天（`stats:prune`），每日汇总长期保留。
