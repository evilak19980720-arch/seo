# 站点管理模块（Site）

管理站群的前台服务器、Cloudflare 账号和站点，并在新增站点时自动完成域名接入和 SSL。

## 安装

```bash
php artisan catch:module:install   # 选择 site，会执行迁移并导入菜单
# 或分步执行
php artisan catch:migrate site
php artisan catch:db:seed site
```

接入流程依赖队列，`.env` 中需设置 `QUEUE_CONNECTION=redis`，并常驻运行：

```bash
php artisan queue:work --tries=0
```

## 使用顺序

1. **服务器**：添加前台渲染服务器（公网 IP、容量，默认 100 站）。
2. **Cloudflare 账号**：填写 Account ID 和 API Token，点“校验 Token”。
   Token 需要的权限：Zone:Edit、DNS:Edit、Zone Settings:Edit、SSL and Certificates:Read，作用于该账号下所有 Zone。
3. **站点列表 → 新增**：填写网站名称和域名，选择 Cloudflare 账号。服务器留空会自动分配有空位的那台。

## 接入状态

| 状态 | 含义 | 需要做什么 |
| --- | --- | --- |
| 待接入 | 已进入队列 | 等待队列执行 |
| 等待 NS 生效 | Zone 已创建，域名 NS 还没指向 Cloudflare | 鼠标悬停状态可看到需要设置的 NS；域名已在该 Cloudflare 账号里则会直接跳过 |
| 等待证书 | DNS（根域名 A/AAAA + www CNAME，开启代理）、SSL Full、Always Use HTTPS 已写入 | 等待 Cloudflare 签发边缘证书，一般几分钟 |
| 已上线 | 证书已签发 | 无 |
| 失败 | 3 天内未完成或接口多次报错 | 悬停查看原因，修正后点“重新接入” |

## 源站证书

当前 SSL 模式设为 **Full**：访客到 Cloudflare 使用 Cloudflare 签发的正式证书，Cloudflare 回源时加密但不校验源站证书。所以每台前台服务器的 Nginx 只需配置一张默认证书（自签证书或任意一张 Cloudflare Origin CA 证书）即可服务所有域名。

源站防火墙务必只放行 [Cloudflare IP 段](https://www.cloudflare.com/ips/)，避免绕过 Cloudflare 直接访问源站。

如需 Full (strict)，需要为每个域名签发 Origin CA 证书并下发到对应服务器，计划在后续版本自动化，届时把 `Jobs/OnboardSite.php` 中的 `SSL_MODE` 改为 `strict`。

## 注意

- Cloudflare 对单个账号每天新增 Zone 的数量有限制，每天接入 50–100 个域名时可能触发，届时可添加多个 Cloudflare 账号分摊。
- 删除站点只释放服务器名额，不会删除 Cloudflare 上的 Zone 和 DNS 记录。
- 站点创建后域名、服务器和 Cloudflare 账号不可在表单中修改；迁移服务器将在后续版本提供。
