<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $socialUser = Socialite::driver($provider)->user();

        $user = User::where('google_id', $socialUser->getId())
            ->orWhere('email', $socialUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $socialUser->getId()]);
            }
        } else {
            $user = DB::transaction(function () use ($socialUser) {
                $user = User::create([
                    'name'              => $socialUser->getName(),
                    'email'             => $socialUser->getEmail(),
                    'google_id'         => $socialUser->getId(),
                    'email_verified_at' => now(),
                    'password'          => bcrypt(Str::random(32)),
                ]);

                $household = Household::create([
                    'name'        => $socialUser->getName() . "'s Hogar",
                    'invite_code' => Household::generateUniqueCode(),
                ]);

                Member::create([
                    'household_id' => $household->id,
                    'user_id'      => $user->id,
                    'name'         => $socialUser->getName(),
                    'role'         => 'admin',
                ]);

                return $user;
            });
        }

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }
}
