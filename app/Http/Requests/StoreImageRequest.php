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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'imageName'=>'required|string|unique:images,name|min:3|max:50',
            'imageDescription'=>'required|string|min:3|max:250',
            'imageType'=>'required|in:1,2,3',
            'imageFile'=>'required|mimes:jpeg,jpg,png,gif|image|size:5120',
        ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'name' => $this->imageName,
            'description' => $this->imageDescription,
            'type' => $this->imageType,
            'file' => $this->imageFile
        ]);
    }
}
