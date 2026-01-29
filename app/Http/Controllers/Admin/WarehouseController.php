<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Redirect;

/**
 * Warehouse Management Controller
 * 
 * Manages warehouse locations used as shipping origins.
 * Warehouses are assigned to customers and packages.
 */
class WarehouseController extends Controller
{
    /**
     * Display list of warehouses
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $warehouses = Warehouse::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            })
            ->withCount(['customers', 'packages'])
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Admin/Warehouses/Report', [
            'warehouses' => $warehouses,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show create warehouse form
     */
    public function create()
    {
        return Inertia::render('Admin/Warehouses/Create');
    }

    /**
     * Store new warehouse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:warehouses,code',
            'company_name' => 'nullable|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'country_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // If setting as default, unset other defaults
            if (!empty($validated['is_default'])) {
                Warehouse::where('is_default', true)->update(['is_default' => false]);
            }

            // If this is the first warehouse, make it default
            if (Warehouse::count() === 0) {
                $validated['is_default'] = true;
                $validated['is_active'] = true;
            }

            Warehouse::create($validated);

            DB::commit();

            return Redirect::route('admin.warehouses.index')
                ->with('alert', 'Warehouse created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Show edit warehouse form
     */
    public function edit(Warehouse $warehouse)
    {
        return Inertia::render('Admin/Warehouses/Edit', [
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Update warehouse
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:10', Rule::unique('warehouses')->ignore($warehouse->id)],
            'company_name' => 'nullable|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'country_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Prevent deactivating the default warehouse
            if ($warehouse->is_default && isset($validated['is_active']) && !$validated['is_active']) {
                return Redirect::back()->withErrors([
                    'is_active' => 'Cannot deactivate the default warehouse. Set another warehouse as default first.'
                ]);
            }

            $warehouse->update($validated);

            DB::commit();

            return Redirect::route('admin.warehouses.index')
                ->with('alert', 'Warehouse updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Set warehouse as default
     */
    public function setDefault(Warehouse $warehouse)
    {
        if (!$warehouse->is_active) {
            return Redirect::back()->withErrors([
                'message' => 'Cannot set inactive warehouse as default.'
            ]);
        }

        try {
            DB::beginTransaction();
            $warehouse->setAsDefault();
            DB::commit();

            return Redirect::route('admin.warehouses.index')
                ->with('alert', "{$warehouse->name} is now the default warehouse.");
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Toggle warehouse active status
     */
    public function toggleActive(Warehouse $warehouse)
    {
        // Cannot deactivate default warehouse
        if ($warehouse->is_default && $warehouse->is_active) {
            return Redirect::back()->withErrors([
                'message' => 'Cannot deactivate the default warehouse. Set another warehouse as default first.'
            ]);
        }

        try {
            $warehouse->update(['is_active' => !$warehouse->is_active]);
            $status = $warehouse->is_active ? 'activated' : 'deactivated';

            return Redirect::route('admin.warehouses.index')
                ->with('alert', "Warehouse {$status} successfully.");
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Get active warehouses for dropdown (API)
     */
    public function getActive()
    {
        $warehouses = Warehouse::active()
            ->select('id', 'name', 'code', 'city', 'state', 'is_default')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }
}
