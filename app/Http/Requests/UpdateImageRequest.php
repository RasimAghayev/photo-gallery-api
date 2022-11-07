<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $data=[];
        $method=$this->method();
        if($method=='PUT'){
            $data= [[
                'imageName'=>'required|string|min:3|max:50',
                'imageDescription'=>'required|string|min:3|max:250',
                'imageType'=>'required|in:1,2,3',
                'imageFile'=>'required|image|mimes:jpeg,jpg,png,gif|size:5120',
            ]
            ];
        }else{
            $data= [[
                'imageName'=>'sometimes|string|min:3|max:50',
                'imageDescription'=>'sometimes|string|min:3|max:250',
                'imageType'=>'sometimes|in:1,2,3',
                'imageFile'=>'sometimes|image|mimes:jpeg,jpg,png,gif|size:5120',
            ]
            ];
        }
        return $data;
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
