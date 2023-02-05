<?php

namespace App\Http\Controllers\Managements;

use App\Http\Controllers\Controller;
use App\Services\UserServices;
use Illuminate\Validation\Rules;

class UserPasswordController extends Controller
{

    public function update(UserServices $userServices, int $id)
    {
        request()->validate([
            'old_password' => ['required','string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = request()->user();
        $userServices->changePassword(request()->password,$user);

        return response()->noContent();
    }
}