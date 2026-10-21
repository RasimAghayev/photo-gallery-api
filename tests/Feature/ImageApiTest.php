<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImageApiTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/image';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
    }

    /**
     * A request that holds every value of the brief.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'imageName' => 'A photo of a cat',
            'imageDescription' => 'A cat that sits on a table.',
            'imageType' => 2,
            'imageFile' => UploadedFile::fake()->image('cat.jpg', 64, 64),
        ], $overrides);
    }

    /**
     * The headers of a request of the suite.
     *
     * The header of the answer asks for JSON, so a request that does not
     * pass the validation is answered with the status 422 and not with a
     * redirect to a page.
     */
    private function headers(array $extra = []): array
    {
        return array_merge(['Accept' => 'application/json'], $extra);
    }

    public function test_the_listing_holds_ten_records_at_a_time(): void
    {
        Image::factory()->count(12)->create();

        $response = $this->getJson(self::ENDPOINT);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonCount(10, 'data');
    }

    public function test_the_listing_holds_only_the_name_the_type_and_the_description(): void
    {
        Image::factory()->create(['name' => 'A photo of a cat', 'type' => 2]);

        $response = $this->getJson(self::ENDPOINT);

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'A photo of a cat')
            ->assertJsonPath('data.0.type', 2);

        // The path of the file was in the answer before. The files live on a
        // private disk, so the path says nothing to a consumer.
        $this->assertArrayNotHasKey('file', $response->json('data.0'));
        $this->assertArrayNotHasKey('imageFile', $response->json('data.0'));
    }

    public function test_an_image_is_created_with_a_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders($this->headers(['Authorization' => 'Bearer '.$token]))
            ->post(self::ENDPOINT, $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'A photo of a cat')
            ->assertJsonPath('data.type', 2)
            ->assertJsonPath('data.description', 'A cat that sits on a table.');

        $this->assertDatabaseHas('images', ['name' => 'A photo of a cat']);

        // The file went to the private disk.
        $image = Image::first();
        $this->assertNotNull($image->file);
        Storage::disk('private')->assertExists($image->file);
    }

    public function test_the_creation_without_a_token_is_refused(): void
    {
        $response = $this->withHeaders($this->headers())
            ->post(self::ENDPOINT, $this->payload());

        $response->assertUnauthorized();
        $this->assertDatabaseCount('images', 0);
    }

    public function test_a_name_that_is_too_long_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->withHeaders($this->headers())
            ->post(self::ENDPOINT, $this->payload(['imageName' => str_repeat('a', 51)]))
            ->assertStatus(422);
    }

    public function test_a_description_that_is_too_long_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->withHeaders($this->headers())
            ->post(self::ENDPOINT, $this->payload(['imageDescription' => str_repeat('a', 251)]))
            ->assertStatus(422);
    }

    public function test_a_type_outside_the_list_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->withHeaders($this->headers())
            ->post(self::ENDPOINT, $this->payload(['imageType' => 4]))
            ->assertStatus(422);
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->withHeaders($this->headers())
            ->post(self::ENDPOINT, $this->payload([
                'imageFile' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ]))->assertStatus(422);
    }

    public function test_a_file_that_is_too_big_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->withHeaders($this->headers())
            ->post(self::ENDPOINT, $this->payload([
                'imageFile' => UploadedFile::fake()->create('big.jpg', 6000, 'image/jpeg'),
            ]))->assertStatus(422);
    }

    public function test_a_name_that_is_already_taken_is_refused(): void
    {
        Image::factory()->create(['name' => 'A photo of a cat']);
        Sanctum::actingAs(User::factory()->create());

        $this->withHeaders($this->headers())
            ->post(self::ENDPOINT, $this->payload())
            ->assertStatus(422);
    }

    public function test_one_image_holds_a_temporary_address_of_its_file(): void
    {
        $image = Image::factory()->create();

        $response = $this->getJson(self::ENDPOINT.'/'.$image->id);

        $response->assertOk()
            ->assertJsonPath('data.name', $image->name)
            ->assertJsonPath('data.type', (int) $image->type);

        $this->assertNotNull($response->json('data.url'));
        $this->assertStringContainsString('signature=', $response->json('data.url'));
    }

    public function test_the_temporary_address_gives_the_file(): void
    {
        $image = Image::factory()->create();
        $url = URL::temporarySignedRoute('image.file', now()->addMinutes(5), ['image' => $image->id]);

        $response = $this->get($url);

        $response->assertOk();
    }

    public function test_an_address_without_a_signature_does_not_give_the_file(): void
    {
        $image = Image::factory()->create();

        $this->get(self::ENDPOINT.'/'.$image->id.'/file')->assertForbidden();
    }

    public function test_an_image_that_does_not_exist_answers_not_found(): void
    {
        $this->getJson(self::ENDPOINT.'/999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Image not found.');
    }

    public function test_an_image_is_updated(): void
    {
        $image = Image::factory()->create(['name' => 'Old name']);
        Sanctum::actingAs(User::factory()->create());

        $this->withHeaders($this->headers())
            ->put(self::ENDPOINT.'/'.$image->id, [
                'imageName' => 'New name',
                'imageDescription' => 'A new description.',
                'imageType' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name')
            ->assertJsonPath('data.type', 3);

        $this->assertDatabaseHas('images', ['id' => $image->id, 'name' => 'New name']);
    }

    public function test_a_new_file_replaces_the_old_one(): void
    {
        $image = Image::factory()->create();
        $previous = $image->file;
        Sanctum::actingAs(User::factory()->create());

        $this->withHeaders($this->headers())
            ->put(self::ENDPOINT.'/'.$image->id, [
                'imageName' => 'A photo of a dog',
                'imageDescription' => 'A dog that runs.',
                'imageType' => 1,
                'imageFile' => UploadedFile::fake()->image('dog.jpg', 64, 64),
            ])->assertOk();

        $image->refresh();

        $this->assertNotSame($previous, $image->file);
        Storage::disk('private')->assertExists($image->file);

        // The file of the old record is not needed any more.
        Storage::disk('private')->assertMissing($previous);
    }

    public function test_an_image_is_deleted_with_its_file(): void
    {
        $image = Image::factory()->create();
        $path = $image->file;
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson(self::ENDPOINT.'/'.$image->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('images', ['id' => $image->id]);

        // The file of the row that is gone has to go as well.
        Storage::disk('private')->assertMissing($path);
    }
}
