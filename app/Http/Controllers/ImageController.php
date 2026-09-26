<?php

namespace App\Http\Controllers;

use App\Http\Resources\ImageCollection;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //Task 3
        return new ImageCollection(Image::orderBy('id', 'desc')->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreImageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreImageRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('images', 'private');
            $validated['file'] = $path;
        }

        return new ImageResource(Image::create($validated));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $image=Image::find($id);
        if($image) {
            return new ImageResource($image);
        }else{
            return 'Image not found.';
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateImageRequest  $request
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateImageRequest $request, Image $image)
    {
        $image->update($request->validated());
        return new ImageResource($image);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function destroy(Image $image)
    {
        $imageFile=Image::exists($image->file);
        return response()->json([
            'id'=>$image->id,
            'status'=>$image->delete(),
            'message'=>'Success'
        ]);
    }
}
