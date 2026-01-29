<?php

namespace App\Repositories;

use App\Interfaces\UserInterface;
use App\Models\User;
use Carbon\Carbon;
use Hash;

/**
 * UserRepository
 * 
 * Handles system user operations (admin, operator, warehouse, support, sales).
 * For customer operations, use CustomerRepository instead.
 */
class UserRepository implements UserInterface
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Find system user by ID
     */
    public function findById($userId)
    {
        return $this->user->find($userId);
    }

    /**
     * Update system user
     */
    public function update($userId, $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        if (isset($data['date_of_birth'])) {
            $data['date_of_birth'] = Carbon::parse($data['date_of_birth'])->format('Y-m-d');
        }
        return $this->user->where('id', $userId)->update($data);
    }

    /**
     * Get paginated system users with search
     */
    public function systemUsers($request, $perPage = 25)
    {
        $query = $this->user->query()->whereIn('type', [
            User::USER_TYPE_ADMIN,
            User::USER_TYPE_OPERATOR,
            User::USER_TYPE_WAREHOUSE,
            User::USER_TYPE_SUPPORT,
            User::USER_TYPE_SALES,
        ]);

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Create system user
     */
    public function store($data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        if (isset($data['date_of_birth']) && $data['date_of_birth']) {
            $data['date_of_birth'] = Carbon::parse($data['date_of_birth'])->format('Y-m-d');
        }
        return $this->user->create($data);
    }

    /**
     * Get system user count
     */
    public function systemUserCount()
    {
        return $this->user->whereIn('type', [
            User::USER_TYPE_ADMIN,
            User::USER_TYPE_OPERATOR,
            User::USER_TYPE_WAREHOUSE,
            User::USER_TYPE_SUPPORT,
            User::USER_TYPE_SALES,
        ])->count();
    }

    /**
     * @deprecated Use CustomerRepository::customers() instead
     */
    public function customers()
    {
        return app(CustomerRepository::class)->customers();
    }

    /**
     * @deprecated Use CustomerRepository::customers_paginated() instead
     */
    public function users($request)
    {
        return app(CustomerRepository::class)->customers_paginated($request);
    }

    /**
     * @deprecated Use CustomerRepository::customerCount() instead
     */
    public function userCount()
    {
        return app(CustomerRepository::class)->customerCount();
    }
}
