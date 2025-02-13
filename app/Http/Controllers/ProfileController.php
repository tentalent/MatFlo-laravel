<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;


class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function changepasswordFrom(Request $request): View
    {
        return view('profile.partials.update-password-form', [
            'user' => $request->user(),
        ]);
    }



    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $file = $request->file('image');
        $request->user()->fill($request->validated());

        if(!is_null($file)) {
            $filename = time() . '_' . str_replace(" ", "_", $file->getClientOriginalName());
            $filePath = 'avatars/' . $filename;

            $response = Storage::disk('s3')->put($filePath, file_get_contents($file));
            $fileUrl = Storage::disk('s3')->url($filePath);
    
            $request->user()->image = $fileUrl;
        }
        

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
            $request->user()->status = 0;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('profile-status', 'Profile updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
