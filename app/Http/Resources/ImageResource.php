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
            'imageName' => $this->name,
            'imageDescription' => $this->description,
            'imageType' => $this->type,
            'imageFile' => $this->file,
            'createdTimestump' => $this->created_at->format('d.m.Y H:i:s'),
            'updatedTimestump' => $this->updated_at->format('d.m.Y H:i:s'),
        ];
    }
}
