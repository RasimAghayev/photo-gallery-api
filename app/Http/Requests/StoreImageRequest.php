<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * The rules of the brief: a name of at most 50 characters, a description
     * of at most 250 characters, a file of at most 5 megabytes and a type of
     * 1, 2 or 3.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'imageName' => ['required', 'string', 'min:3', 'max:50', Rule::unique('images', 'name')],
            'imageDescription' => ['required', 'string', 'min:3', 'max:250'],
            'imageType' => ['required', 'in:1,2,3'],
            'imageFile' => ['required', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
        ];
    }

    /**
     * The messages of the rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'imageName.required' => 'A name for the image is required.',
            'imageName.unique' => 'A record with this name already exists.',
            'imageDescription.required' => 'A description for the image is required.',
            'imageType.required' => 'A type for the image is required.',
            'imageType.in' => 'The type of the image is 1, 2 or 3.',
            'imageFile.required' => 'A file for the image is required.',
            'imageFile.max' => 'The file of the image is at most 5 megabytes.',
        ];
    }
}
