<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PackageChangeRequestController extends Controller
{
    /**
     * Display list of change requests for admin review
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $query = PackageChangeRequest::with([
            'package:id,package_id,from,store_tracking_id,weight,length,width,height,total_value',
            'user:id,name,email',
            'reviewer:id,name',
        ])->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20);

        // Get counts for tabs
        $counts = [
            'pending' => PackageChangeRequest::pending()->count(),
            'approved' => PackageChangeRequest::where('status', 'approved')->count(),
            'rejected' => PackageChangeRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/ChangeRequests/Index', [
            'requests' => $requests,
            'counts' => $counts,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Show a specific change request
     */
    public function show($id)
    {
        $changeRequest = PackageChangeRequest::with([
            'package.items',
            'package.files',
            'user:id,name,email,phone',
            'reviewer:id,name',
        ])->findOrFail($id);

        return Inertia::render('Admin/ChangeRequests/Show', [
            'changeRequest' => $changeRequest,
        ]);
    }

    /**
     * Approve a change request
     */
    public function approve(Request $request, $id)
    {
        $changeRequest = PackageChangeRequest::pending()->findOrFail($id);

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $changeRequest->approve(Auth::id(), $validated['admin_notes'] ?? null);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Change request approved and applied to package.',
                ]);
            }

            return redirect()->back()->with('success', 'Change request approved and applied to package.');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve change request.',
                ], 500);
            }

            return redirect()->back()->withErrors(['message' => 'Failed to approve change request.']);
        }
    }

    /**
     * Reject a change request
     */
    public function reject(Request $request, $id)
    {
        $changeRequest = PackageChangeRequest::pending()->findOrFail($id);

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        try {
            $changeRequest->reject(Auth::id(), $validated['admin_notes']);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Change request rejected.',
                ]);
            }

            return redirect()->back()->with('success', 'Change request rejected.');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject change request.',
                ], 500);
            }

            return redirect()->back()->withErrors(['message' => 'Failed to reject change request.']);
        }
    }

    /**
     * Bulk approve/reject change requests
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:package_change_requests,id',
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $requests = PackageChangeRequest::whereIn('id', $validated['ids'])
            ->pending()
            ->get();

        $processed = 0;
        foreach ($requests as $changeRequest) {
            if ($validated['action'] === 'approve') {
                $changeRequest->approve(Auth::id(), $validated['admin_notes']);
            } else {
                $changeRequest->reject(Auth::id(), $validated['admin_notes']);
            }
            $processed++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$processed} request(s) {$validated['action']}d successfully.",
        ]);
    }
}
