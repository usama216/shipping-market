<?php

namespace App\Imports;

use App\Models\User;
use Hash;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow, WithChunkReading, ShouldQueue
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        $user = User::where('email', $row['email'])->first();
        if (!$user) {
            return new User([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'user_name' => $row['first_name'] . '_' . $row['last_name'] . Str::random(4),
                'email' => $row['email'],
                'phone' => $row['phone'],
                'password' => Hash::make('market123'),
                'suite' => $row['suite'],
                'country' => $row['country'],
                'is_old' => 1,
                'is_active' => 1,
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
