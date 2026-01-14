<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TranslationSeeder extends Seeder
{
    private const TOTAL = 100000;
    private const CHUNK_SIZE = 1000;

    public function run(): void
    {
        $tags = Tag::all()->pluck('id')->toArray();

        Translation::factory()
            ->count(self::TOTAL)
            ->create()
            ->chunk(self::CHUNK_SIZE)
            ->each(function ($translations) use ($tags) {
                foreach ($translations as $translation) {
                    $translation->tags()->attach(
                        collect($tags)->random(rand(1, 2))->toArray()
                    );
                }
            });
    }
}
