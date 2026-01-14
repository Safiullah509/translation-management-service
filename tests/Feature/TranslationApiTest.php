<?php

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_can_create_translation_with_tags(): void
    {
        $this->signIn();
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);

        $response = $this->postJson('/api/translations', [
            'key' => 'auth.login.title',
            'content' => 'Welcome back',
            'locale' => $locale->code,
            'tags' => ['web', 'mobile'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('key', 'auth.login.title')
            ->assertJsonPath('locale.code', 'en');

        $this->assertDatabaseHas('translations', [
            'key' => 'auth.login.title',
            'content' => 'Welcome back',
            'locale_id' => $locale->id,
        ]);

        $this->assertDatabaseHas('tags', ['name' => 'web']);
        $this->assertDatabaseHas('tags', ['name' => 'mobile']);
    }

    public function test_can_list_translations_with_pagination(): void
    {
        $this->signIn();
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        Translation::factory()->count(3)->create(['locale_id' => $locale->id]);

        $response = $this->getJson('/api/translations');

        $response->assertOk();

        $payload = $response->json();
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('links', $payload);
        if (array_key_exists('meta', $payload)) {
            $this->assertArrayHasKey('current_page', $payload['meta']);
        } else {
            $this->assertArrayHasKey('current_page', $payload);
            $this->assertArrayHasKey('last_page', $payload);
        }
    }

    public function test_can_search_translations_by_tag(): void
    {
        $this->signIn();
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $webTag = Tag::create(['name' => 'web']);
        $mobileTag = Tag::create(['name' => 'mobile']);

        $webTranslation = Translation::factory()->create(['locale_id' => $locale->id]);
        $webTranslation->tags()->sync([$webTag->id]);

        $mobileTranslation = Translation::factory()->create(['locale_id' => $locale->id]);
        $mobileTranslation->tags()->sync([$mobileTag->id]);

        $response = $this->getJson('/api/translations/search?tag=web');

        $response->assertOk()
            ->assertJsonFragment(['id' => $webTranslation->id])
            ->assertJsonMissing(['id' => $mobileTranslation->id]);
    }

    public function test_can_update_translation_content_and_tags(): void
    {
        $this->signIn();
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $translation = Translation::factory()->create(['locale_id' => $locale->id]);

        $response = $this->putJson("/api/translations/{$translation->id}", [
            'content' => 'Updated content',
            'tags' => ['desktop'],
        ]);

        $response->assertOk()
            ->assertJsonPath('content', 'Updated content');

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'content' => 'Updated content',
        ]);
        $this->assertDatabaseHas('tags', ['name' => 'desktop']);
    }

    public function test_can_delete_translation(): void
    {
        $this->signIn();
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $translation = Translation::factory()->create(['locale_id' => $locale->id]);

        $response = $this->deleteJson("/api/translations/{$translation->id}");

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Deleted successfully']);
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_can_export_translations_by_locale_and_tag(): void
    {
        $this->signIn();
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $tag = Tag::create(['name' => 'web']);
        $translation = Translation::factory()->create([
            'locale_id' => $locale->id,
            'key' => 'home.title',
            'content' => 'Welcome',
        ]);
        $translation->tags()->sync([$tag->id]);

        $response = $this->getJson('/api/export/en?tag=web');

        $response->assertOk();

        $payload = $response->json();
        $this->assertArrayHasKey('home.title', $payload);
        $this->assertSame($translation->id, $payload['home.title']['id'] ?? null);
        $this->assertSame('Welcome', $payload['home.title']['content'] ?? null);
    }
}
