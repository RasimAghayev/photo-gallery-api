<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImageRequest extends FormRequest
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
     * A change of a record has to hold every value, because the rows of the
     * table do not accept an empty one. The file is the exception: a change
     * without a file keeps the file that is already there.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $image = $this->route('image');
        $id = is_object($image) ? $image->id : $image;

        return [
            'imageName' => [
                'required', 'string', 'min:3', 'max:50',
                Rule::unique('images', 'name')->ignore($id),
            ],
            'imageDescription' => ['required', 'string', 'min:3', 'max:250'],
            'imageType' => ['required', 'in:1,2,3'],
            'imageFile' => ['sometimes', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
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
            'imageFile.max' => 'The file of the image is at most 5 megabytes.',
        ];
    }
}
