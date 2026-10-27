<?php

declare(strict_types=1);

namespace Modules\Site\Models;

use Catch\Base\CatchModel as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Jobs\OnboardSite;

/**
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property string $locale
 * @property int $server_id
 * @property int $cloudflare_account_id
 * @property string $zone_id
 * @property array|null $name_servers
 * @property int $onboard_status
 * @property string $onboard_error
 * @property int $online_at
 * @property int $status
 * @property Server|null $server
 * @property CloudflareAccount|null $cloudflareAccount
 */
class Site extends Model
{
    protected $table = 'sites';

    protected $fillable = [
        'id', 'name', 'domain', 'locale', 'title', 'description', 'keywords', 'logo', 'game_brief',
        'server_id', 'cloudflare_account_id', 'zone_id', 'name_servers', 'onboard_status', 'onboard_error', 'online_at',
        'status', 'creator_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected array $fields = [
        'id', 'name', 'domain', 'locale', 'logo', 'server_id', 'cloudflare_account_id', 'name_servers',
        'onboard_status', 'onboard_error', 'online_at', 'status', 'created_at',
    ];

    protected array $form = ['name', 'domain', 'locale', 'title', 'description', 'keywords', 'logo', 'game_brief', 'server_id', 'cloudflare_account_id', 'status'];

    public array $searchable = [
        'name' => 'like',
        'domain' => 'like',
        'server_id' => '=',
        'onboard_status' => '=',
        'status' => '=',
    ];

    protected $casts = [
        'name_servers' => 'array',
        'server_id' => 'integer',
        'cloudflare_account_id' => 'integer',
        'onboard_status' => 'integer',
        'online_at' => 'integer',
        'status' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
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
     * 新增站点：分配服务器后进入接入队列
     */
    public function createSite(array $data): int
    {
        $data['domain'] = static::normalizeDomain($data['domain']);

        $site = DB::transaction(function () use ($data) {
            $server = app(Server::class)->allocate(empty($data['server_id']) ? null : (int) $data['server_id']);
            $data['server_id'] = $server->id;
            $data['onboard_status'] = OnboardStatus::Pending->value;

            return static::query()->find($this->storeBy($data));
        });

        OnboardSite::dispatch($site->id)->afterCommit();

        return $site->id;
    }

    /**
     * 编辑站点：域名、服务器和 Cloudflare 账号创建后不在这里修改，避免和已写入的 DNS 不一致
     */
    public function updateSite(int $id, array $data): mixed
    {
        unset($data['domain'], $data['server_id'], $data['cloudflare_account_id']);

        return $this->updateBy($id, $data);
    }

    public function deleteSite(int $id): mixed
    {
        $site = static::query()->findOrFail($id);

        return DB::transaction(function () use ($site, $id) {
            app(Server::class)->release($site->server_id);

            return $this->deleteBy($id);
        });
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
