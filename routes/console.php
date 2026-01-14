<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('translations:perf {--count=100000}', function () {
    $count = (int) $this->option('count');

    $locale = \App\Models\Locale::firstOrCreate(
        ['code' => 'en'],
        ['name' => 'English']
    );
    $tag = \App\Models\Tag::firstOrCreate(['name' => 'web']);

    $this->info("Seeding {$count} translations...");
    $start = microtime(true);

    \App\Models\Translation::factory()
        ->count($count)
        ->create(['locale_id' => $locale->id])
        ->each(fn (\App\Models\Translation $translation) => $translation->tags()->sync([$tag->id]));

    $elapsedMs = (microtime(true) - $start) * 1000;
    $this->info("Seeded {$count} translations in " . number_format($elapsedMs, 2) . 'ms.');

    $this->info('Tip: run PERF_TESTS=1 php artisan test to exercise performance assertions.');
})->purpose('Seed a large dataset for performance testing.');
