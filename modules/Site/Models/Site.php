<?php

declare(strict_types=1);

namespace Modules\Site\Models;

use Catch\Base\CatchModel as Model;
use Catch\Exceptions\FailedException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Front\Models\FrontSetting;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Jobs\GenerateGameProfile;
use Modules\Site\Jobs\OnboardSite;
use Modules\Site\Jobs\SyncSiteDns;

/**
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property string $locale
 * @property string $ip
 * @property int $cloudflare_account_id
 * @property string $zone_id
 * @property array|null $name_servers
 * @property int $onboard_status
 * @property string $onboard_error
 * @property int $online_at
 * @property int $status
 * @property CloudflareAccount|null $cloudflareAccount
 * @property GameProfile|null $profile
 */
class Site extends Model
{
    protected $table = 'sites';

    protected $fillable = [
        'id', 'name', 'domain', 'locale', 'title', 'description', 'keywords', 'logo', 'game_brief',
        'cloudflare_account_id', 'ip', 'zone_id', 'name_servers', 'onboard_status', 'onboard_error', 'online_at',
        'status', 'creator_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected array $fields = [
        'id', 'name', 'domain', 'locale', 'logo', 'ip', 'cloudflare_account_id', 'name_servers',
        'onboard_status', 'onboard_error', 'online_at', 'status', 'created_at',
    ];

    protected array $form = ['name', 'domain', 'locale', 'title', 'description', 'keywords', 'logo', 'game_brief', 'ip', 'cloudflare_account_id', 'status'];

    public array $searchable = [
        'name' => 'like',
        'domain' => 'like',
        'ip' => '=',
        'onboard_status' => '=',
        'status' => '=',
    ];

    protected $casts = [
        'name_servers' => 'array',
        'cloudflare_account_id' => 'integer',
        'onboard_status' => 'integer',
        'online_at' => 'integer',
        'status' => 'integer',
    ];

    public function template(): HasOne
    {
        return $this->hasOne(SiteTemplate::class, 'site_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(GameProfile::class, 'site_id');
    }

    public function cloudflareAccount(): BelongsTo
    {
        return $this->belongsTo(CloudflareAccount::class, 'cloudflare_account_id');
    }

    /**
     * 统一域名格式：去掉协议、www、路径和端口，转小写
     */
    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = (string) preg_replace('#^[a-z]+://#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];

        return (string) preg_replace('/^www\./', '', rtrim($domain, '.'));
    }

    /**
     * 新增站点：进入接入队列，同时生成游戏资料和模板
     */
    public function createSite(array $data): int
    {
        $data['domain'] = static::normalizeDomain($data['domain']);
        $data['ip'] = ($data['ip'] ?? '') ?: (string) FrontSetting::get('default_ip');
        if ($data['ip'] === '') {
            throw new FailedException('请填写解析 IP，或在「全局配置」里设置默认解析 IP');
        }
        $data['onboard_status'] = OnboardStatus::Pending->value;
        $template = ['reference_image' => (string) ($data['reference_image'] ?? ''), 'style_notes' => (string) ($data['style_notes'] ?? '')];
        unset($data['reference_image'], $data['style_notes']);

        $site = static::query()->find($this->storeBy($data));
        SiteTemplate::forSite($site->id)->update($template);
        app(SiteTemplate::class)->generate($site->id);

        OnboardSite::dispatch($site->id);
        GameProfile::forSite($site->id);
        GenerateGameProfile::dispatch($site->id);

        return $site->id;
    }

    /**
     * 编辑站点：域名和 Cloudflare 账号创建后不能修改；修改 IP 会同步更新解析
     */
    public function updateSite(int $id, array $data): mixed
    {
        unset($data['domain'], $data['cloudflare_account_id'], $data['reference_image'], $data['style_notes']);
        $site = static::query()->findOrFail($id);
        $ipChanged = ! empty($data['ip']) && $data['ip'] !== $site->ip;
        if (array_key_exists('ip', $data) && ! $data['ip']) {
            unset($data['ip']);
        }

        $updated = $this->updateBy($id, $data);

        if ($ipChanged && $site->zone_id) {
            SyncSiteDns::dispatch($site->id);
        }

        return $updated;
    }

    public function deleteSite(int $id): mixed
    {
        return $this->deleteBy($id);
    }

    /**
     * 重新进入接入队列（失败后重试，或 NS 刚改好想立即检查）
     */
    public function retryOnboard(int $id): bool
    {
        $site = static::query()->findOrFail($id);
        if ($site->onboard_status === OnboardStatus::Failed->value) {
            $site->update(['onboard_status' => OnboardStatus::Pending->value, 'onboard_error' => '']);
        }

        OnboardSite::dispatch($site->id);

        return true;
    }
}
