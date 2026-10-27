<?php

declare(strict_types=1);

namespace Modules\Content\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Illuminate\Http\Request;
use Modules\Content\Http\Requests\TopicRequest;
use Modules\Content\Models\Topic;

/**
 * @group 内容管理
 *
 * @subgroup 选题
 */
class TopicsController extends Controller
{
    public function __construct(
        protected readonly Topic $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->setBeforeGetList(fn ($query) => $query->with('site:id,name,domain'))->getList();
    }

    public function store(TopicRequest $request): mixed
    {
        return $this->model->storeBy($request->validated() + ['status' => 1, 'source' => 2]);
    }

    public function show($id): mixed
    {
        return $this->model->firstBy($id);
    }

    public function update($id, TopicRequest $request): mixed
    {
        $data = $request->validated();
        unset($data['site_id']);

        return $this->model->updateBy($id, $data);
    }

    public function destroy($id): mixed
    {
        return $this->model->deleteBy($id);
    }

    /**
     * AI 生成选题
     */
    public function generate(Request $request): bool
    {
        $data = $request->validate(['site_id' => ['required', 'integer'], 'count' => ['sometimes', 'integer', 'min:1', 'max:20']]);

        return $this->model->generate((int) $data['site_id'], (int) ($data['count'] ?? 5));
    }

    /**
     * 用选题生成文章
     */
    public function write($id): int
    {
        return Topic::writeArticle((int) $id);
    }
}
