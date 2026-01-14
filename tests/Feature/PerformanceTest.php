<?php

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    private function seedPerfData(int $count = 500): void
    {
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $tag = Tag::create(['name' => 'web']);

        Translation::factory()
            ->count($count)
            ->create(['locale_id' => $locale->id])
            ->each(fn (Translation $translation) => $translation->tags()->sync([$tag->id]));
    }

    public function test_index_endpoint_is_fast(): void
    {
        if (!filter_var(env('PERF_TESTS', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Set PERF_TESTS=1 to run performance tests.');
        }

        $this->signIn();
        $this->seedPerfData();

        $maxMs = (int) (env('PERF_MAX_INDEX_MS', 200) ?: 200);

        $start = microtime(true);
        $response = $this->getJson('/api/translations');
        $elapsedMs = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(
            $maxMs,
            $elapsedMs,
            "Index endpoint exceeded {$maxMs}ms (actual: {$elapsedMs}ms)."
        );
    }

    public function test_export_endpoint_is_fast(): void
    {
        if (!filter_var(env('PERF_TESTS', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Set PERF_TESTS=1 to run performance tests.');
        }

        $this->signIn();
        $this->seedPerfData();

        $maxMs = (int) (env('PERF_MAX_EXPORT_MS', 500) ?: 500);

        $start = microtime(true);
        $response = $this->getJson('/api/export/en?tag=web');
        $elapsedMs = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(
            $maxMs,
            $elapsedMs,
            "Export endpoint exceeded {$maxMs}ms (actual: {$elapsedMs}ms)."
        );
    }
}
