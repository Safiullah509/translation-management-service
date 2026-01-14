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
use OpenApi\Annotations as OA;

class TranslationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/translations",
     *     tags={"Translations"},
     *     summary="List translations",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Paginated translations",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Translation")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return Translation::with(['locale', 'tags'])
            ->paginate(50);
    }

    /**
     * @OA\Get(
     *     path="/translations/{translation}",
     *     tags={"Translations"},
     *     summary="Get a translation",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="translation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation",
     *         @OA\JsonContent(ref="#/components/schemas/Translation")
     *     )
     * )
     */
    public function show(Translation $translation)
    {
        return $translation->load(['locale', 'tags']);
    }

    /**
     * @OA\Post(
     *     path="/translations",
     *     tags={"Translations"},
     *     summary="Create a translation",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"key","content","locale"},
     *             @OA\Property(property="key", type="string", example="auth.login.title"),
     *             @OA\Property(property="content", type="string", example="Welcome back"),
     *             @OA\Property(property="locale", type="string", example="en"),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="string", example="web")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(ref="#/components/schemas/Translation")
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/translations/{translation}",
     *     tags={"Translations"},
     *     summary="Update a translation",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="translation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="content", type="string", example="Updated content"),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="string", example="mobile")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(ref="#/components/schemas/Translation")
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/translations/{translation}",
     *     tags={"Translations"},
     *     summary="Delete a translation",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="translation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(Translation $translation)
    {
        $translation->delete();

        $this->bumpTranslationsCacheVersion();
        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/translations/search",
     *     tags={"Translations"},
     *     summary="Search translations",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="key", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="content", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="locale", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="tag", in="query", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated translations",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Translation")
     *             )
     *         )
     *     )
     * )
     */
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
