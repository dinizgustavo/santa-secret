<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendLoginCode;
use App\Actions\Auth\VerifyLoginCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendLoginCodeRequest;
use App\Http\Requests\Auth\VerifyLoginCodeRequest;
use App\Support\Auth\SignInOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Passwordless sign-in: the person types their e-mail, receives a short code and types it back.
 */
class LoginCodeController extends Controller
{
    private const SESSION_KEY = 'login_code.email';

    public function create(Request $request): Response
    {
        abort_unless(SignInOptions::loginCode(), 404);

        return Inertia::render('auth/login-code', [
            'email' => $request->session()->get(self::SESSION_KEY),
            'codeLength' => config()->integer('auth.login_code.length'),
        ]);
    }

    public function store(SendLoginCodeRequest $request, SendLoginCode $sendLoginCode): RedirectResponse
    {
        abort_unless(SignInOptions::loginCode(), 404);

        try {
            $sendLoginCode->handle($request->email());
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            throw ValidationException::withMessages(['email' => __('messages.login_code.send_failed')]);
        }

        $request->session()->put(self::SESSION_KEY, $request->email());

        return to_route('login-code.edit');
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        abort_unless(SignInOptions::loginCode(), 404);

        $email = $request->session()->get(self::SESSION_KEY);

        if (! is_string($email)) {
            return to_route('login-code.create');
        }

        return Inertia::render('auth/login-code-verify', [
            'email' => $email,
            'codeLength' => config()->integer('auth.login_code.length'),
        ]);
    }

    public function verify(VerifyLoginCodeRequest $request, VerifyLoginCode $verifyLoginCode): RedirectResponse
    {
        abort_unless(SignInOptions::loginCode(), 404);

        $email = $request->session()->get(self::SESSION_KEY);

        if (! is_string($email)) {
            return to_route('login-code.create');
        }

        $user = $verifyLoginCode->handle($email, $request->code());

        Auth::login($user, remember: true);
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
