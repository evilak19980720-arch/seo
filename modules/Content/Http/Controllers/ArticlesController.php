<?php

declare(strict_types=1);

namespace Modules\Content\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Modules\Content\Http\Requests\ArticleRequest;
use Modules\Content\Models\Article;

/**
 * @group 内容管理
 *
 * @subgroup 文章
 */
class ArticlesController extends Controller
{
    public function __construct(
        protected readonly Article $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->setBeforeGetList(fn ($query) => $query->with('site:id,name,domain'))->getList();
    }

    public function store(ArticleRequest $request): mixed
    {
        return $this->model->saveArticle(null, $request->validated());
    }

    public function show($id): mixed
    {
        return Article::query()->with('site:id,name,domain')->findOrFail($id);
    }

    public function update($id, ArticleRequest $request): mixed
    {
        return $this->model->saveArticle((int) $id, $request->validated());
    }

    public function destroy($id): mixed
    {
        return $this->model->deleteArticle((int) $id);
    }

    public function publish($id): bool
    {
        return $this->model->publish((int) $id);
    }

    public function unpublish($id): bool
    {
        return $this->model->unpublish((int) $id);
    }

    public function rewrite($id): bool
    {
        return $this->model->rewrite((int) $id);
    }
}
