<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use Catch\Base\CatchModel as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property int $type
 * @property int $channel_id
 * @property string|null $system_prompt
 * @property string $content
 * @property int $version
 * @property int $status
 * @property Channel|null $channel
 */
class Prompt extends Model
{
    protected $table = 'ai_prompts';

    protected $fillable = ['id', 'name', 'type', 'channel_id', 'system_prompt', 'content', 'version', 'status', 'creator_id', 'created_at', 'updated_at', 'deleted_at'];

    protected array $fields = ['id', 'name', 'type', 'channel_id', 'version', 'status', 'created_at', 'updated_at'];

    protected array $form = ['name', 'type', 'channel_id', 'system_prompt', 'content', 'status'];

    public array $searchable = [
        'name' => 'like',
        'type' => '=',
        'channel_id' => '=',
        'status' => '=',
    ];

    protected $casts = [
        'type' => 'integer',
        'channel_id' => 'integer',
        'version' => 'integer',
        'status' => 'integer',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /**
     * 修改提示词内容时版本号 +1，调用日志会记录版本，方便对比效果
     */
    public function updatePrompt(int $id, array $data): mixed
    {
        $prompt = static::query()->findOrFail($id);

        if (($data['content'] ?? $prompt->content) !== $prompt->content
            || ($data['system_prompt'] ?? $prompt->system_prompt) !== $prompt->system_prompt) {
            $data['version'] = $prompt->version + 1;
        }

        return $this->updateBy($id, $data);
    }

    /**
     * 用变量替换 {name} 占位符，未提供的变量保持原样
     *
     * @param  array<string, scalar|array|null>  $variables
     */
    public static function render(?string $template, array $variables): string
    {
        $replace = [];
        foreach ($variables as $key => $value) {
            $replace['{'.$key.'}'] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) $value;
        }

        return strtr((string) $template, $replace);
    }
}
