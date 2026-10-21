<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Http\Resources\ImageDetailResource;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    /**
     * The disk the files of the gallery live on.
     */
    private const DISK = 'private';

    /**
     * Display a listing of the resource.
     *
     * The brief of the task asks for ten records at a time.
     */
    public function index(Request $request): JsonResponse
    {
        $images = Image::orderByDesc('id')->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Images retrieved successfully.',
            'data' => ImageResource::collection($images)->resolve($request),
            'links' => [
                'first' => $images->url(1),
                'last' => $images->url($images->lastPage()),
                'prev' => $images->previousPageUrl(),
                'next' => $images->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $images->currentPage(),
                'last_page' => $images->lastPage(),
                'per_page' => $images->perPage(),
                'from' => $images->firstItem(),
                'to' => $images->lastItem(),
                'total' => $images->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreImageRequest $request): JsonResponse
    {
        // The fields of the request carry the names of the brief, and the
        // columns of the row carry their own. The file of the request is an
        // object of the request, so it cannot be written into the row: the
        // file goes to the private disk first, and the row holds the path
        // of it.
        $path = $request->file('imageFile')->store('images', self::DISK);

        $image = Image::create([
            'name' => $request->imageName,
            'description' => $request->imageDescription,
            'type' => $request->imageType,
            'file' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image created successfully.',
            'data' => new ImageResource($image),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $image = Image::find($id);

        if (is_null($image)) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image retrieved successfully.',
            'data' => new ImageDetailResource($image),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateImageRequest $request, Image $image): JsonResponse
    {
        $previous = $image->file;
        $file = $request->file('imageFile');

        $image->update([
            'name' => $request->imageName,
            'description' => $request->imageDescription,
            'type' => $request->imageType,
        ]);

        if ($file) {
            $image->file = $file->store('images', self::DISK);
            $image->save();

            // The file of the old record is not needed any more, so it is
            // removed from the disk.
            if ($previous) {
                Storage::disk(self::DISK)->delete($previous);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Image updated successfully.',
            'data' => new ImageDetailResource($image),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Image $image): JsonResponse
    {
        $path = $image->file;

        $image->delete();

        // The row is gone, so the file of it has to go as well, otherwise
        // the disk keeps the files of the records that no longer exist.
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
            'data' => ['id' => $image->id],
        ]);
    }

    /**
     * The file of an image.
     *
     * The address of this endpoint carries a signature, so the file of the
     * private disk is only reachable with a temporary address that the
     * application handed out.
     */
    public function file(Image $image): Response
    {
        if (blank($image->file) || ! Storage::disk(self::DISK)->exists($image->file)) {
            return response()->json([
                'success' => false,
                'message' => 'Image file not found.',
            ], 404);
        }

        return Storage::disk(self::DISK)->download($image->file);
    }
}
