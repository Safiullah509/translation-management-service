<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Http\Request;

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
        $locale = Locale::where('code', $request->locale)->firstOrFail();

        $translation = Translation::create([
            'key' => $request->key,
            'content' => $request->content,
            'locale_id' => $locale->id,
        ]);

        if ($request->tags) {
            $tagIds = Tag::whereIn('name', $request->tags)->pluck('id');
            $translation->tags()->sync($tagIds);
        }

        
        Cache::tags(['translations'])->flush();
        return response()->json($translation->load(['locale', 'tags']), 201);
    }

    public function update(UpdateTranslationRequest $request, Translation $translation)
    {
        $translation->update($request->only('content'));

        if ($request->has('tags')) {
            $tagIds = Tag::whereIn('name', $request->tags)->pluck('id');
            $translation->tags()->sync($tagIds);
        }

        Cache::tags(['translations'])->flush();
        return $translation->load(['locale', 'tags']);
    }

    public function destroy(Translation $translation)
    {
        $translation->delete();

        Cache::tags(['translations'])->flush();
        return response()->json(['message' => 'Deleted successfully']);
        
    }

    public function search(Request $request)
    {
        $query = Translation::query()
            ->with(['locale', 'tags']);

        if ($request->key) {
            $query->where('key', 'like', "%{$request->key}%");
        }

        if ($request->content) {
            $query->where('content', 'like', "%{$request->content}%");
        }

        if ($request->locale) {
            $query->whereHas('locale', function ($q) use ($request) {
                $q->where('code', $request->locale);
            });
        }

        if ($request->tag) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }

        return $query->paginate(50);
    }
}