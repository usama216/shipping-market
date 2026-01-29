<?php

namespace App\Http\Controllers;

use App\Models\PackageItem;
use App\Models\PackageFile;
use App\Repositories\PackageItemRepository;
use App\Repositories\PackageFileRepository;
use App\Traits\CommonTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PackageItemController extends Controller
{
    use CommonTrait;

    protected $packageItemRepository;
    protected $packageFileRepository;

    public function __construct(
        PackageItemRepository $packageItemRepository,
        PackageFileRepository $packageFileRepository
    ) {
        $this->packageItemRepository = $packageItemRepository;
        $this->packageFileRepository = $packageFileRepository;
    }

    /**
     * Store a new package item with multiple images
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'item_note' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'value_per_unit' => 'required|numeric|min:0',
            'total_line_weight' => 'required|numeric|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the package item
            $itemData = $request->only([
                'package_id',
                'title',
                'description',
                'item_note',
                'quantity',
                'value_per_unit',
                'total_line_weight'
            ]);

            $itemData['total_line_value'] = $request->quantity * $request->value_per_unit;

            $packageItem = $this->packageItemRepository->store($itemData);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $this->addFile($image, 'storage/app/public/package_items/');

                    $this->packageFileRepository->insertOne([
                        'package_id' => $request->package_id,
                        'package_item_id' => $packageItem->id,
                        'name' => $image->getClientOriginalName(),
                        'file' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Package item created successfully',
                'item' => $packageItem->load('packageFiles')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating package item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a package item and its images
     */
    public function update(Request $request, PackageItem $packageItem)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'item_note' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'value_per_unit' => 'required|numeric|min:0',
            'total_line_weight' => 'required|numeric|min:0',
            'new_images.*' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'delete_image_ids' => 'nullable|array',
            'delete_image_ids.*' => 'exists:package_files,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update the package item
            $itemData = $request->only([
                'title',
                'description',
                'item_note',
                'quantity',
                'value_per_unit',
                'total_line_weight'
            ]);

            $itemData['total_line_value'] = $request->quantity * $request->value_per_unit;

            $packageItem->update($itemData);

            // Delete selected images
            if ($request->has('delete_image_ids') && is_array($request->delete_image_ids)) {
                foreach ($request->delete_image_ids as $imageId) {
                    $image = PackageFile::find($imageId);
                    if ($image && $image->package_item_id == $packageItem->id) {
                        // Delete file from storage
                        if (Storage::exists($image->file)) {
                            Storage::delete($image->file);
                        }
                        $image->delete();
                    }
                }
            }

            // Upload new images
            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $image) {
                    $path = $this->addFile($image, 'storage/app/public/package_items/');

                    $this->packageFileRepository->insertOne([
                        'package_id' => $packageItem->package_id,
                        'package_item_id' => $packageItem->id,
                        'name' => $image->getClientOriginalName(),
                        'file' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Package item updated successfully',
                'item' => $packageItem->load('packageFiles')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating package item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a package item and all its images
     */
    public function destroy(PackageItem $packageItem)
    {
        try {
            DB::beginTransaction();

            // Delete all associated images
            foreach ($packageItem->packageFiles as $file) {
                if (Storage::exists($file->file)) {
                    Storage::delete($file->file);
                }
            }

            // Delete the item
            $packageItem->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Package item deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting package item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get package item with images
     */
    public function show(PackageItem $packageItem)
    {
        $packageItem->load('packageFiles');

        return response()->json([
            'success' => true,
            'item' => $packageItem
        ]);
    }
}
