<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use Catch\Base\CatchModel as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    protected $table = 'ai_call_logs';

    protected $fillable = ['id', 'channel_id', 'prompt_id', 'prompt_version', 'type', 'site_id', 'model', 'input_tokens', 'output_tokens', 'duration_ms', 'status', 'error', 'created_at', 'updated_at', 'deleted_at'];

    protected array $fields = ['id', 'channel_id', 'prompt_id', 'prompt_version', 'type', 'site_id', 'model', 'input_tokens', 'output_tokens', 'duration_ms', 'status', 'error', 'created_at'];

    public array $searchable = [
        'channel_id' => '=',
        'prompt_id' => '=',
        'type' => '=',
        'site_id' => '=',
        'status' => '=',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'prompt_id');
    }
}
