<?php

namespace App\Services\Auth;

use App\Contracts\Auth\SchoolSsoAdapter;

class MockSchoolSsoAdapter implements SchoolSsoAdapter
{
    public function authenticate(string $token): array
    {
        return [
            'id' => 1,
            'name' => 'Mock User',
            'email' => 'mockuser@example.com',
            'role' => 'admin',
        ];
    }
}