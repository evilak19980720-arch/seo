<?php

declare(strict_types=1);

use Illuminate\Database\Seeder;
use Modules\Ai\Enums\PromptType;
use Modules\Ai\Models\Prompt;
use Modules\Common\Support\ImportPermissions;

return new class extends Seeder
{
    public function run(): void
    {
        ImportPermissions::import($this->menus());

        foreach ($this->prompts() as $prompt) {
            if (! Prompt::query()->where('type', $prompt['type'])->exists()) {
                app(Prompt::class)->createBy($prompt + ['channel_id' => 0, 'version' => 1, 'status' => 1]);
            }
        }
    }

    public function menus(): array
    {
        return [
            $this->node([
                'permission_name' => 'AI 管理',
                'route' => '/ai',
                'icon' => 'cpu-chip',
                'permission_mark' => '',
                'component' => '/layout/index.vue',
                'type' => 1,
                'sort' => 3,
            ], [
                $this->menu('AI 渠道', 'channels', 'channels', '/ai/channels/index.vue', 1, ['test' => '测试', 'options' => '选项']),
                $this->menu('提示词', 'prompts', 'prompts', '/ai/prompts/index.vue', 2, ['types' => '类型']),
                $this->menu('调用日志', 'logs', 'callLogs', '/ai/logs/index.vue', 3, ['summary' => '汇总'], ['index' => '列表']),
            ]),
        ];
    }

    /**
     * 默认提示词。站点语言通过 {locale} 传入，巴西站点为 pt-BR
     */
    protected function prompts(): array
    {
        $editor = 'You are the senior editor of the official website of an independent game studio. You write for real players, in natural native {locale}. '
            .'Never invent facts that are not in the provided game information: no release dates, prices, download numbers, ratings, awards, platforms or features that are not given. '
            .'If information is missing, write in general terms or leave the field empty instead of guessing.';

        return [
            [
                'name' => '生成游戏资料（默认）',
                'type' => PromptType::GameProfile->value,
                'system_prompt' => $editor,
                'content' => <<<'TXT'
Create a first draft of the official profile for the game "{site_name}". Language: {locale}.

Information from the studio (may be empty):
{game_brief}

The studio will review and correct this draft before publishing, so keep every statement consistent with the information above.

Return only JSON with this structure:
{
  "summary": "2-3 sentence description of the game",
  "genre": "short genre label",
  "gameplay": "one paragraph explaining how the game is played",
  "features": ["4 to 6 short feature bullet points"],
  "faq": [{"q": "question a new player would ask", "a": "answer"}],
  "title": "home page <title>, max 60 characters, includes the game name",
  "description": "home page meta description, max 155 characters",
  "keywords": "5 to 8 comma separated keywords players would search"
}
TXT,
            ],
            [
                'name' => '生成选题（默认）',
                'type' => PromptType::Topic->value,
                'system_prompt' => $editor,
                'content' => <<<'TXT'
Game: {site_name}. Language: {locale}.
Game information (the only source of facts):
{game_profile}

Articles already published on this site (do not repeat or overlap them):
{existing_titles}

Suggest {count} new article topics that a real player of this game would search for and that can be answered using only the game information: beginner guides, how specific mechanics work, tips for a feature, FAQ-style explanations, update notes. Each topic must target a different search intent.

Return only JSON:
{"topics": [{"title": "working title", "keyword": "main search keyword", "intent": "guide | explanation | tips | faq | news"}]}
TXT,
            ],
            [
                'name' => '生成文章（默认）',
                'type' => PromptType::ArticleGenerate->value,
                'system_prompt' => $editor,
                'content' => <<<'TXT'
Write an article for the official website of "{site_name}". Language: {locale}.

Topic: {topic}
Main keyword: {keyword}
Length: between {min_words} and {max_words} words.

Game information (the only source of facts):
{game_profile}

Rules:
- Answer the reader's question early, then go into detail. Use concrete steps and examples taken from the game information.
- Use the keyword naturally; no keyword stuffing.
- Use Markdown: one "# " title on the first line, "## " sections, short paragraphs, lists where useful.
- No generic introductions like "In the world of gaming", no conclusions that only repeat the article.
- Do not mention that you are an AI and do not add facts that are not in the game information.

Return only the Markdown article.
TXT,
            ],
            [
                'name' => '润色文章（默认）',
                'type' => PromptType::ArticlePolish->value,
                'system_prompt' => $editor,
                'content' => <<<'TXT'
Edit the following article for the official website of "{site_name}" so it reads like it was written by an experienced native {locale} editor who plays the game.

- Keep the meaning, structure, headings and every fact. Do not add new facts.
- Remove filler, repetition, generic phrases and overly formal or promotional wording.
- Use natural {locale} vocabulary and the friendly, direct tone of a game studio talking to its players.
- Keep Markdown formatting and the "# " title on the first line.

Game information (to check facts):
{game_profile}

Article:
{article}

Return only the edited Markdown article.
TXT,
            ],
            [
                'name' => '审核文章（默认）',
                'type' => PromptType::ArticleReview->value,
                'system_prompt' => 'You are a strict quality reviewer for a game studio website. You decide whether an article is good enough to publish. Be critical: most first drafts should not get more than 80.',
                'content' => <<<'TXT'
Review this {locale} article for the official website of "{site_name}".

Game information (the only accepted source of facts):
{game_profile}

Articles already published on the site:
{existing_titles}

Article:
{article}

Score each check from 0 to 100:
- factual: every statement is supported by the game information, nothing invented
- useful: answers a real player question with specific, actionable content
- original: adds information or angle not already covered by the published articles
- language: natural, correct native {locale}, no machine-like phrasing
- honest: no exaggeration, clickbait or misleading claims

Return only JSON:
{"factual": 0, "useful": 0, "original": 0, "language": 0, "honest": 0, "issues": ["specific problems to fix"]}
TXT,
            ],
            [
                'name' => '生成站点模板（默认）',
                'type' => PromptType::SiteTemplate->value,
                'system_prompt' => 'You are a senior web designer. You design the visual theme of an official game website. You only output CSS and static HTML for the header and footer; you never write JavaScript.',
                'content' => <<<'TXT'
Design the visual theme of the official website of the game "{site_name}" (language {locale}, genre: {genre}).
Reference image attached: {has_image}. If there is one, follow its colors, mood, typography and layout feeling closely, but do not copy text or logos from it.
Style notes from the studio: {style_notes}

The page body is generated by the system and uses this markup (style it, do not change it):
- <main><div class="wrap"> … </div></main> wraps every page; .wrap is the centered content column
- h1, h2, h3, p, ul, details/summary (FAQ), img
- .btn (primary call-to-action link) and .btn.ghost (secondary)
- .grid (responsive grid) containing .card > img + .body > h3 > a, .muted
- .prose (article text), .breadcrumb, .muted (secondary text), .slot (ad slot)
- The page also uses CSS variables you must define in :root: --bg, --fg, --muted, --line, --brand, --soft

Write:
1. css: complete CSS for the variables, body, the classes above, and your header and footer. Mobile first, readable font sizes, good contrast, no external fonts or images except system fonts, no @import, no url() except data: SVG patterns (URL-encode < as %3C).
2. header_html: the site header. Use these placeholders exactly: [[logo]] (an <img> tag or empty), [[site_name]], [[nav]] (navigation links), [[cta]] (the main "play" button or empty).
3. footer_html: the site footer. Use [[site_name]], [[year]], [[footer_text]] and optionally [[nav]].

Only static HTML in header and footer: no <script>, no inline event handlers, no forms, no iframes, no external links.

Return only JSON:
{"css": "...", "header_html": "...", "footer_html": "..."}
TXT,
            ],
            [
                'name' => '生成标题与描述（默认）',
                'type' => PromptType::Seo->value,
                'system_prompt' => $editor,
                'content' => <<<'TXT'
Write search metadata in {locale} for this article on the official website of "{site_name}".

Article:
{article}

Return only JSON:
{"meta_title": "max 60 characters, accurate, includes the main keyword", "meta_description": "max 155 characters, describes what the reader will learn", "slug": "short-lowercase-ascii-slug"}
TXT,
            ],
        ];
    }

    /**
     * @param  array<string, string>  $extraActions
     * @param  array<string, string>|null  $actions
     */
    protected function menu(string $name, string $route, string $controller, string $component, int $sort, array $extraActions = [], ?array $actions = null): array
    {
        $actions = ($actions ?? ['index' => '列表', 'store' => '新增', 'show' => '读取', 'update' => '更新', 'destroy' => '删除', 'enable' => '禁用/启用']) + $extraActions;

        $buttons = [];
        $buttonSort = 1;
        foreach ($actions as $action => $label) {
            $buttons[] = $this->node([
                'permission_name' => $label,
                'route' => '',
                'permission_mark' => "{$controller}@{$action}",
                'component' => '',
                'type' => 3,
                'sort' => $buttonSort++,
            ]);
        }

        return $this->node([
            'permission_name' => $name,
            'route' => $route,
            'permission_mark' => $controller,
            'component' => $component,
            'type' => 2,
            'sort' => $sort,
        ], $buttons);
    }

    protected function node(array $attributes, array $children = []): array
    {
        $node = $attributes + [
            'parent_id' => 0,
            'icon' => '',
            'module' => 'ai',
            'redirect' => '',
            'keepalive' => 1,
            'hidden' => 1,
            'active_menu' => '',
            'creator_id' => 1,
        ];

        if ($children) {
            $node['children'] = $children;
        }

        return $node;
    }
};
