<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'name' => $this->imageName,
            'description' => $this->imageDescription,
            'type' => $this->imageType,
            'file' => $this->imageFile,
            'created_at' => $this->createdTimestump->format('d.m.Y H:i:s'),
            'updated_at' => $this->updatedTimestump->format('d.m.Y H:i:s'),
        ];
    }
}
