<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('mypage.profile', compact('user'));
    }

    public function edit()
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        return view('mypage.profile', compact('user'));
    }

    public function update(ProfileRequest $request)
    {
        $user = auth()->user();

        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $path = $image->store('profile_images', 'public');
            $user->profile_image_path = $path;
        }

        $user->name = $request->input('name');
        $user->save();

        $user->address()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'postal_code' => $request->input('postal_code'),
                'address' => $request->input('address'),
                'building' => $request->input('building'),
            ]
            );
            return redirect()->route('mypage');
    }
}
