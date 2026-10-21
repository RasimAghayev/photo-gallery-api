<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * One image with the temporary address of its file.
 *
 * The brief of the task asks for the name, the type, the description and a
 * temporary address of the image. The address carries a signature and stops
 * working after five minutes, so the file of the private disk never becomes
 * reachable without the key of the application.
 */
class ImageDetailResource extends JsonResource
{
    /**
     * The number of minutes the address of the file is valid.
     */
    public const MINUTES = 5;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'type' => (int) $this->type,
            'description' => $this->description,
            'url' => $this->temporaryUrl(),
            'expires_in' => self::MINUTES * 60,
        ];
    }

    /**
     * The temporary address of the file of the image.
     */
    private function temporaryUrl(): ?string
    {
        if (blank($this->file) || blank($this->id)) {
            return null;
        }

        return URL::temporarySignedRoute(
            'image.file',
            now()->addMinutes(self::MINUTES),
            ['image' => $this->id]
        );
    }
}
