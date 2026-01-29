<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PackageStatus;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageSpecialRequestResponse;
use App\Models\PackageSpecialRequestPhoto;
use App\Traits\CommonTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageSpecialRequestController extends Controller
{
    use CommonTrait;

    /**
     * Store or update admin response for a special request
     */
    public function storeResponse(Request $request, Package $package)
    {
        try {
            $request->validate([
                'special_request_id' => 'required|exists:special_requests,id',
                'admin_note' => 'nullable|string|max:5000',
                'photos' => 'nullable|array',
                'photos.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,tif,tiff,webp,pdf|max:10240',
            ]);

            DB::beginTransaction();

            // Get or create response
            $response = PackageSpecialRequestResponse::firstOrCreate(
                [
                    'package_id' => $package->id,
                    'special_request_id' => $request->special_request_id,
                ],
                [
                    'admin_id' => Auth::id(),
                    'admin_note' => $request->admin_note,
                ]
            );

            // Update note if provided
            if ($request->has('admin_note')) {
                $response->admin_note = $request->admin_note;
                $response->admin_id = Auth::id();
                $response->save();
            }

            // Handle photo uploads
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $this->addFile($file, 'storage/app/public/special_request_photos/');
                    $fileType = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'pdf';

                    PackageSpecialRequestPhoto::create([
                        'package_special_request_response_id' => $response->id,
                        'name' => $file->getClientOriginalName(),
                        'file' => $path,
                        'file_type' => $fileType,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Response saved successfully.',
                'response' => $response->load('photos', 'specialRequest', 'admin'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save special request response', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to save response: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark special request as completed and optionally mark package as ready to send
     */
    public function markCompleted(Request $request, Package $package)
    {
        try {
            $request->validate([
                'special_request_id' => 'required|exists:special_requests,id',
                'mark_ready_to_send' => 'nullable|boolean',
            ]);

            DB::beginTransaction();

            $response = PackageSpecialRequestResponse::where('package_id', $package->id)
                ->where('special_request_id', $request->special_request_id)
                ->firstOrFail();

            $response->is_completed = true;
            $response->save();

            // If all special requests are completed, optionally mark package as ready to send
            if ($request->boolean('mark_ready_to_send')) {
                $allCompleted = $this->checkAllSpecialRequestsCompleted($package);
                
                if ($allCompleted) {
                    $package->status = PackageStatus::READY_TO_SEND;
                    $package->save();
                } else {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Cannot mark as ready to send. Not all special requests are completed.',
                    ], 422);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Special request marked as completed.',
                'package_status' => $package->fresh()->status,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to mark as completed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a photo from special request response
     */
    public function deletePhoto(PackageSpecialRequestPhoto $photo)
    {
        try {
            // Verify the photo belongs to a response for a package the admin can access
            $response = $photo->response;
            if (!$response) {
                return response()->json(['message' => 'Photo not found.'], 404);
            }

            $photo->delete();

            return response()->json(['message' => 'Photo deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete photo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check if all special requests for a package are completed
     */
    private function checkAllSpecialRequestsCompleted(Package $package): bool
    {
        $selectedAddonIds = $package->selected_addon_ids ?? [];
        
        if (empty($selectedAddonIds)) {
            return true; // No special requests, so technically all are "completed"
        }

        $completedCount = PackageSpecialRequestResponse::where('package_id', $package->id)
            ->whereIn('special_request_id', $selectedAddonIds)
            ->where('is_completed', true)
            ->count();

        return $completedCount === count($selectedAddonIds);
    }
}
