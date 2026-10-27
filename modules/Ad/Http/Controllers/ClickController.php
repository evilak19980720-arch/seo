<?php

declare(strict_types=1);

namespace Modules\Ad\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Ad\Models\Ad;

class ClickController
{
    public function __invoke($id): RedirectResponse
    {
        $link = Ad::click((int) $id);
        abort_unless($link, 404);

        return redirect()->away($link, 302)->withHeaders([
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'no-store',
        ]);
    }
}
