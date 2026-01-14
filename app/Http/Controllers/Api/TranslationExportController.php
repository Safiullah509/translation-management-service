<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class TranslationExportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/export/{locale}",
     *     tags={"Export"},
     *     summary="Export translations as JSON",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="en")
     *     ),
     *     @OA\Parameter(
     *         name="tag",
     *         in="query",
     *         @OA\Schema(type="string", example="web")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Export payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\AdditionalProperties(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="Welcome back")
     *             )
     *         )
     *     )
     * )
     */
    public function export(string $locale, Request $request)
    {
        $tag = $request->query('tag');

        $translations = Translation::query()
            ->whereHas('locale', fn ($q) => $q->where('code', $locale))
            ->when($tag, fn ($q) =>
                $q->whereHas('tags', fn ($t) => $t->where('name', $tag))
            )
            ->get(['id', 'key', 'content'])
            ->mapWithKeys(fn ($row) => [
                $row->key => ['id' => $row->id, 'content' => $row->content],
            ])
            ->toArray();

        return response()->json($translations)
            ->header('Cache-Control', 'public, max-age=60');
    }
}
