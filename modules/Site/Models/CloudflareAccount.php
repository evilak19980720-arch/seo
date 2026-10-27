<?php

declare(strict_types=1);

namespace Modules\Site\Models;

use Catch\Base\CatchModel as Model;
use Catch\Enums\Status;
use Modules\Site\Support\Cloudflare;

/**
 * @property int $id
 * @property string $name
 * @property string $account_id
 * @property string $api_token
 * @property int $status
 * @property string $remark
 */
class CloudflareAccount extends Model
{
    protected $table = 'site_cloudflare_accounts';

    protected $fillable = ['id', 'name', 'account_id', 'api_token', 'status', 'remark', 'creator_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['api_token'];

    protected array $fields = ['id', 'name', 'account_id', 'status', 'remark', 'created_at'];

    protected array $form = ['name', 'account_id', 'api_token', 'status', 'remark'];

    public array $searchable = [
        'name' => 'like',
        'account_id' => '=',
        'status' => '=',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'status' => 'integer',
    ];

    public function client(): Cloudflare
    {
        return new Cloudflare(trim((string) $this->api_token));
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    public function options(): array
    {
        return static::query()
            ->where('status', Status::Enable->value)
            ->orderBy('id')
            ->get(['id', 'name', 'account_id'])
            ->map(fn (self $account) => ['label' => $account->name, 'value' => $account->id])
            ->all();
    }
}
