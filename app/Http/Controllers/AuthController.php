<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\User\AfterRegister;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Auth\Events\Registered;



class AuthController extends Controller
{

    public function login()
    {
        return view('auth.login');
    }

    public function google()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleProviderCallback()
    {

        $callback = Socialite::driver('google')->stateless()->user();
        $data = [

            'name' => $callback->getName(),
            'password' => Hash::make('password123'),
            'username' => $callback->getName(),
            'email' => $callback->getEmail(),
            'email_verified_at' => date('Y-m-d H:i:s', time()),
        ];
        // $user = User::firstOrCreate(['email' => $data['email']], $data);
        $user = User::whereEmail($data['email'])->first();
        if (!$user) {
            $user = User::create($data);
            // Mail::to($user->email)->send(new AfterRegister($user));
        }
        Auth::login($user, true);
        return redirect(route('dashboard'));
    }

    public function loginPost(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);


        $credentials = $request->only('username', 'password');
        $remember = $request->has('remember'); // Mendapatkan nilai remember dari input form

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if ($user->type == 'admin') {
                return redirect()->route('dashboard')->withSuccess("Welcome to Dashboard Admin");
            } else if ($user->type == 'creator') {
                return redirect()->route('dashboard')->withSuccess("Welcome to Dashboard Creator");
            } else {
                return redirect()->route('login')->withUnsuccess("Your account is not active.");
            }
        }

        return redirect()->route('login')->withErrors([
            'username' => 'Invalid username or password.',
        ]);
    }

    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();

        return redirect('/');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function registerPost(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users|min:5|max:20|alpha_dash',
            'email' => 'required|email|unique:users|max:50',
            'password' => 'required|min:8|max:20|confirmed',
        ], [
            'username.required' => 'Username is required.',
            'username.unique' => 'Username is already taken.',
            'username.min' => 'Username must be at least 5 characters.',
            'username.max' => 'Username cannot exceed 20 characters.',
            'username.alpha_dash' => 'Username can only contain letters, numbers, dashes, and underscores.',
            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 5 characters.',
            'name.max' => 'Name cannot exceed 50 characters.',
            'name.regex' => 'Name can only contain letters and spaces.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email cannot exceed 50 characters.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password cannot exceed 20 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.confirmed.required' => 'Password is required.',
            'password.confirmed.min' => 'Password must be at least 8 characters.',
            'password.confirmed.max' => 'Password cannot exceed 20 characters.',
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            // 'email_verified_at' => date('Y-m-d H:i:s', time())
        ]);
        event(new Registered($user));
        Auth::login($user, $request->get('remember'));


        return redirect('/email/verify');
    }
}
