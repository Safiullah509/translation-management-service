<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(title="Translation Management API", version="1.0.0"),
 *     @OA\Server(url="/api"),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="sanctum",
 *             type="http",
 *             scheme="bearer"
 *         ),
 *         @OA\Schema(
 *             schema="Locale",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="code", type="string", example="en")
 *         ),
 *         @OA\Schema(
 *             schema="Tag",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="web")
 *         ),
 *         @OA\Schema(
 *             schema="Translation",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="key", type="string", example="auth.login.title"),
 *             @OA\Property(property="content", type="string", example="Welcome back"),
 *             @OA\Property(property="locale", ref="#/components/schemas/Locale"),
 *             @OA\Property(
 *                 property="tags",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/Tag")
 *             )
 *         )
 *     )
 * )
 */
class OpenApi
{
}
