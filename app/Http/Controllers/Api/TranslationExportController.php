<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TranslationExportController extends Controller
{
    public function export(string $locale, Request $request)
    {
        $tag = $request->query('tag');

        $version = (int) Cache::get('translations.cache_version', 1);
        $cacheKey = "translations.export.v{$version}.{$locale}." . ($tag ?? 'all');

        $translations = Cache::remember(
            $cacheKey,
            60,
            function () use ($locale, $tag) {
                return Translation::query()
                    ->whereHas('locale', fn ($q) => $q->where('code', $locale))
                    ->when($tag, fn ($q) =>
                        $q->whereHas('tags', fn ($t) => $t->where('name', $tag))
                    )
                    ->pluck('content', 'key');
            }
        );

        return response()->json($translations)
            ->header('Cache-Control', 'public, max-age=60');
    }
}
