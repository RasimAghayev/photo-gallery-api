<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name'=>'required|string|unique:images,name|min:3|max:50',
            'description'=>'required|string|min:3|max:250',
            'type'=>'required|in:1,2,3',
            'file'=>'required|mimes:jpeg,jpg,png,gif|image|size:5120',
        ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'imageName' => $this->name,
            'imageDescription' => $this->description,
            'imageType' => $this->type,
            'imageFile' => $this->file
        ]);
    }
}
