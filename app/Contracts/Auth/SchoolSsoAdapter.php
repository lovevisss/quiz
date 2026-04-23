<?php

namespace App\Contracts\Auth;

interface SchoolSsoAdapter
{
    public function authenticate(string $token): array;
}