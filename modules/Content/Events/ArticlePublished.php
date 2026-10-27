<?php

declare(strict_types=1);

namespace Modules\Content\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Content\Models\Article;

/**
 * 文章发布或已发布文章被修改，前台据此清缓存、更新 sitemap
 */
class ArticlePublished
{
    use Dispatchable;

    public function __construct(
        public readonly Article $article
    ) {
    }
}
