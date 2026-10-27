<?php

declare(strict_types=1);

namespace Modules\Content\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Content\Models\Article;

/**
 * 文章下线或删除
 */
class ArticleUnpublished
{
    use Dispatchable;

    public function __construct(
        public readonly Article $article
    ) {
    }
}
