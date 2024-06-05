<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Twilio\Rest\Client;
use Str;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'mobile' => ['sometimes', 'string',  'max:255', 'unique:' . User::class],
            'profile' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'gender' => ['required'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $profile = "";
        if ($request->file('profile')) {
            $profile = $request->file('profile')->store('profiles','public');

        }

        $user = User::create([
            'uuid' => Str::uuid(),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'gender' => $request->gender,
            'profile' => $profile ?? "",
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);
        if ($request->email) {
            auth()->user()->sendEmailVerificationNotification();
            return redirect(RouteServiceProvider::VERIFY)->with('status', 'verification-link-sent');
        } else {
            $user = User::find(auth()->id());
            $otp = random_int(100000, 999999);
            $user->mobile_verification_code = $otp;
            $user->save();
            return redirect(RouteServiceProvider::HOME);

           
        }
    }
}
