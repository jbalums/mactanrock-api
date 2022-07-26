<?php

namespace App\Http\Controllers\Managements;

use App\Http\Controllers\Controller;
use App\Services\UserServices;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;

class PasswordController extends Controller
{

    public function update(Request $request, UserServices $userServices)
    {
        $request->validate([
            'old_password' => ['required','string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = $request->user();

        $userServices->changePassword($request->password,$user);

        return response()->json(['msg' => 'Password  reset'],200);


    }
}