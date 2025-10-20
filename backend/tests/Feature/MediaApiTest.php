<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_upload_image_file_successfully()
    {
        Sanctum::actingAs($this->user);

        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $response = $this->postJson('/api/media/upload', [
            'file' => $image,
            'alt' => 'Test image alt text',
            'title' => 'Test Image Title'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Media uploaded successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'file_name',
                        'mime_type',
                        'path',
                        'disk',
                        'file_hash',
                        'collection',
                        'alt',
                        'title',
                        'size',
                        'width',
                        'height'
                    ],
                    'message'
                ]);

        // Verify file was stored
        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    /** @test */
    public function it_validates_required_file_upload()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/media/upload', [
            'alt' => 'Test alt text'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_can_list_media_files()
    {
        Sanctum::actingAs($this->user);

        // Create test media
        Media::factory()->create([
            'name' => 'Test Media',
            'file_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/test-image.jpg',
            'alt' => 'Test alt text',
            'title' => 'Test Title',
        ]);

        $response = $this->getJson('/api/media');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'file_name',
                                'mime_type',
                                'path',
                                'alt',
                                'title',
                                'size',
                                'width',
                                'height',
                                'created_at'
                            ]
                        ],
                        'total',
                        'per_page',
                        'current_page'
                    ],
                    'message'
                ]);
    }

    /** @test */
    public function it_can_retrieve_specific_media_file()
    {
        Sanctum::actingAs($this->user);

        $media = Media::factory()->create([
            'name' => 'Test Media',
            'file_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/test-image.jpg',
            'alt' => 'Test alt text',
            'title' => 'Test Title',
        ]);

        $response = $this->getJson("/api/media/{$media->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $media->id,
                        'name' => 'Test Media',
                        'file_name' => 'test-image.jpg',
                        'alt' => 'Test alt text',
                        'title' => 'Test Title',
                    ]
                ]);
    }

    /** @test */
    public function it_can_update_media_metadata()
    {
        Sanctum::actingAs($this->user);

        $media = Media::factory()->create([
            'name' => 'Test Media',
            'alt' => 'Original alt text',
            'title' => 'Original Title',
        ]);

        $response = $this->putJson("/api/media/{$media->id}", [
            'alt' => 'Updated alt text',
            'title' => 'Updated Title',
            'name' => 'Updated Media Name'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Media updated successfully'
                ]);

        $media->refresh();
        $this->assertEquals('Updated alt text', $media->alt);
        $this->assertEquals('Updated Title', $media->title);
        $this->assertEquals('Updated Media Name', $media->name);
    }

    /** @test */
    public function it_can_delete_media_file()
    {
        Sanctum::actingAs($this->user);

        $media = Media::factory()->create([
            'name' => 'Test Media',
            'file_name' => 'test-image.jpg',
            'path' => 'media/test-image.jpg',
        ]);

        Storage::disk('public')->put('media/test-image.jpg', 'fake image content');

        $response = $this->deleteJson("/api/media/{$media->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Media deleted successfully'
                ]);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing('media/test-image.jpg');
    }

    /** @test */
    public function it_requires_authentication_for_media_operations()
    {
        $response = $this->getJson('/api/media');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_unauthorized_media_access()
    {
        $otherUser = User::factory()->create();
        $otherMedia = Media::factory()->create([
            'user_id' => $otherUser->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/media/{$otherMedia->id}");

        $response->assertStatus(404);
    }
}