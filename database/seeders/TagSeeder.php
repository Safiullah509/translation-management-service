<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['web', 'mobile', 'desktop'];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['name' => $tag]);
        }
    }
}
