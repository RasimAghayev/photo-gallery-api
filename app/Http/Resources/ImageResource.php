<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The summary of an image.
 *
 * The brief of the task asks for the name, the type and the description of a
 * record. The path of the file is not part of it, and it is not part of this
 * answer either: the files live on a private disk and their path says
 * nothing to a consumer.
 */
class ImageResource extends JsonResource
{
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
        ];
    }
}
