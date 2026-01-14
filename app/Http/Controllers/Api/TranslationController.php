<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TranslationController extends Controller
{
    public function index()
    {
        return Translation::with(['locale', 'tags'])
            ->paginate(50);
    }

    public function show(Translation $translation)
    {
        return $translation->load(['locale', 'tags']);
    }

    public function store(StoreTranslationRequest $request)
    {
        $data = $request->validated();
        $locale = Locale::where('code', $data['locale'])->firstOrFail();

        $translation = Translation::create([
            'key' => $data['key'],
            'content' => $data['content'],
            'locale_id' => $locale->id,
        ]);

        if (!empty($data['tags'])) {
            $this->syncTags($translation, $data['tags']);
        }

        $this->bumpTranslationsCacheVersion();
        return response()->json($translation->load(['locale', 'tags']), 201);
    }

    public function update(UpdateTranslationRequest $request, Translation $translation)
    {
        $data = $request->validated();
        $translation->update(['content' => $data['content'] ?? $translation->content]);

        if ($request->has('tags')) {
            $this->syncTags($translation, $data['tags'] ?? []);
        }

        $this->bumpTranslationsCacheVersion();
        return $translation->load(['locale', 'tags']);
    }

    public function destroy(Translation $translation)
    {
        $translation->delete();

        $this->bumpTranslationsCacheVersion();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function search(Request $request)
    {
        $query = Translation::query()
            ->with(['locale', 'tags']);

        if ($request->filled('key')) {
            $query->where('key', 'like', '%' . $request->key . '%');
        }

        if ($request->filled('content')) {
            $query->where('content', 'like', '%' . $request->content . '%');
        }

        if ($request->filled('locale')) {
            $query->whereHas('locale', function ($q) use ($request) {
                $q->where('code', $request->locale);
            });
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }

        return $query->paginate(50);
    }

    private function syncTags(Translation $translation, array $tagNames): void
    {
        $tagNames = array_values(array_filter(array_map('trim', $tagNames)));
        if ($tagNames === []) {
            $translation->tags()->sync([]);
            return;
        }

        $tagIds = collect($tagNames)
            ->map(fn ($name) => Tag::firstOrCreate(['name' => $name])->id)
            ->all();

        $translation->tags()->sync($tagIds);
    }

    private function bumpTranslationsCacheVersion(): void
    {
        $key = 'translations.cache_version';
        $nextVersion = ((int) Cache::get($key, 1)) + 1;
        Cache::forever($key, $nextVersion);
    }
}
