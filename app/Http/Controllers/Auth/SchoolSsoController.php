<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Auth\SchoolSsoAdapter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolSsoController
{
    protected SchoolSsoAdapter $ssoAdapter;

    public function __construct(SchoolSsoAdapter $ssoAdapter)
    {
        $this->ssoAdapter = $ssoAdapter;
    }

    public function mockCallback(Request $request)
    {
        $ssoUser = $this->ssoAdapter->authenticate($request->input('token'));

        $user = User::firstOrCreate(
            ['email' => $ssoUser['email']],
            ['name' => $ssoUser['name'], 'password' => bcrypt('default-password')]
        );

        Auth::login($user);

        return response()->json($ssoUser);
    }
}