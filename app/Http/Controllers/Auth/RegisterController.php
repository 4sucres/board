<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\User;
use App\Models\VerifyUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use TimeHunter\LaravelGoogleReCaptchaV3\Validations\GoogleReCaptchaV3ValidationRule;
use App\Models\Achievement;

class RegisterController extends Controller
{
    public function register()
    {
        return view('auth.register');
    }

    protected function submit()
    {
        $min_date = now()->subYears(13);
        $max_date = now()->setYear(1900);

        request()->validate([
            'name' => ['required', 'string', 'alpha_dash', 'max:255', 'min:4', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'dob' => ['required', 'date', 'before:' . $min_date, 'after:' . $max_date],
            'gender' => ['required', 'in:M,F'],
            'g-recaptcha-response' => [new GoogleReCaptchaV3ValidationRule('register_action')],
        ], [
            'gender.in' => 'Désolé, pas de sucres non genrés ici. Tu peux trouver ta place sur <a href="http://www.madmoizelle.com/">mademoiZelle.com</a>',
            'dob.before' => 'Tu dois avoir plus de 13 ans pour t\'inscrire ici.',
            'dob.after' => 'WTF l\'ancien !? T\'es né avant 1900?',
        ]);

        $user = User::create([
            'name' => request()->name,
            'display_name' => request()->name,
            'shown_role' => 'Sucrette',
            'email' => request()->email,
            'password' => Hash::make(request()->password),
            'gender' => request()->gender,
            'dob' => request()->dob,
        ]);
        $user->assignRole('user');

        switch (request()->referrer) {
            case 'none.none':
                $user->achievements()->attach(Achievement::where('name', 'Esprit libre')->first());
                break;
            case 'avenoel.org':
                $user->achievements()->attach(Achievement::where('name', 'Noëliste')->first());
                break;
            case 'jeuxvideo.com':
                $user->achievements()->attach(Achievement::where('name', 'ISSOU !')->first());
                break;
            case '2sucres.org':
                $user->achievements()->attach(Achievement::where('name', 'Ça fait 6 sucres')->first());
                break;
            case 'lebunker.net':
                $user->achievements()->attach(Achievement::where('name', 'Bunkered')->first());
                break;
            case 'onche.party':
                $user->achievements()->attach(Achievement::where('name', 'Tu veux du ponche ?')->first());
                break;
        }

        $verify_user = VerifyUser::create([
            'user_id' => $user->id,
            'token' => str_random(40),
        ]);

        Mail::to($user)->send(new VerifyEmail($user));

        return redirect()->route('home')
            ->with('success', 'Ton compte a bien été créé le sucre ! Tu dois valider ton adresse e-mail pour finaliser ton inscription !');
    }

    public function verify($token)
    {
        $verify_user = VerifyUser::where('token', $token)->firstOrFail();
        $user = $verify_user->user;

        $user->email_verified_at = now();
        $user->save();

        auth()->login($user);

        $verify_user->delete();

        return redirect()->route('home')
            ->with('success', 'Bienvenue à bord ' . $user->name . ' !');
    }
}
