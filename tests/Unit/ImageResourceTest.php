<?php

namespace Tests\Unit;

use App\Http\Resources\ImageDetailResource;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImageResourceTest extends TestCase
{
    private function image(array $attributes = []): Image
    {
        // The key of the row is not part of the fields that can be filled, so
        // the model is built with forceFill.
        return (new Image)->forceFill(array_merge([
            'id' => 1,
            'name' => 'A photo of a cat',
            'description' => 'A cat that sits on a table.',
            'type' => '2',
            'file' => 'images/cat.jpg',
        ], $attributes));
    }

    public function test_the_summary_holds_the_name_the_type_and_the_description(): void
    {
        $resource = new ImageResource($this->image());

        $this->assertSame([
            'name' => 'A photo of a cat',
            'type' => 2,
            'description' => 'A cat that sits on a table.',
        ], $resource->toArray(Request::create('/api/v1/image')));
    }

    public function test_the_summary_does_not_hold_the_path_of_the_file(): void
    {
        $resource = new ImageResource($this->image());

        // The path of the file says nothing to a consumer, and the files live
        // on a private disk.
        $this->assertArrayNotHasKey('file', $resource->toArray(Request::create('/api/v1/image')));
    }

    public function test_the_detail_holds_a_temporary_address_of_the_file(): void
    {
        $resource = new ImageDetailResource($this->image());

        $array = $resource->toArray(Request::create('/api/v1/image'));

        $this->assertArrayHasKey('url', $array);
        $this->assertStringContainsString('signature=', $array['url']);
        $this->assertSame(300, $array['expires_in']);
    }

    public function test_the_detail_of_an_image_without_a_file_has_no_address(): void
    {
        $resource = new ImageDetailResource($this->image(['file' => null]));

        $this->assertNull($resource->toArray(Request::create('/api/v1/image'))['url']);
    }

    public function test_the_type_of_an_image_is_a_number(): void
    {
        $image = $this->image();

        $this->assertSame(2, $image->type);
    }
}
