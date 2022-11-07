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
                'name'=>'required|string|min:3|max:50',
                'description'=>'required|string|min:3|max:250',
                'type'=>'required|in:1,2,3',
                'file'=>'required|image|mimes:jpeg,jpg,png,gif|size:5120',
            ]
            ];
        }else{
            $data= [[
                'name'=>'sometimes|string|min:3|max:50',
                'description'=>'sometimes|string|min:3|max:250',
                'type'=>'sometimes|in:1,2,3',
                'file'=>'sometimes|image|mimes:jpeg,jpg,png,gif|size:5120',
            ]
            ];
        }
        return $data;
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
