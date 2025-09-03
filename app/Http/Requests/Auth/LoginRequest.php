<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Student;

class LoginRequest extends FormRequest
{
    // Define the maximum number of login attempts before lockout
    public const MAX_ATTEMPTS = 3;

    // Define the lockout duration in seconds
    public const LOCKOUT_DURATION = 180; // 3 minutes

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login_field' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->login_field)
            ->orWhere('contact_info', $this->login_field)
            ->first();

        // The debug log is now safe from the "property on null" error
        // because of the `optional()` helper.
        Log::info('Login attempt', [
            'login_field' => $this->login_field,
            'user_role' => optional($user)->role ?? 'null',
            'student' => optional(optional($user)->student)->toArray(),
        ]);

        // This check correctly returns a generic "auth.failed" message if no user is found.
        if (! $user) {
            RateLimiter::hit($this->throttleKey(), self::LOCKOUT_DURATION);
            throw ValidationException::withMessages([
                'login_field' => trans('auth.failed'),
            ]);
        }

        // This is where the old error happened. The user must be
        // a student before we check their status.
        if ($user->student) {
            if ((int) $user->student->status === 1) {
                RateLimiter::hit($this->throttleKey(), self::LOCKOUT_DURATION);
                throw ValidationException::withMessages([
                    'login_field' => 'Your account has been deactivated please contact your ADMINISTRATOR.',
                ]);
            }
        }

        // Check the password. This is only reached if a user was found
        // and, if they are a student, they are not deactivated.
        if (! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($this->throttleKey(), self::LOCKOUT_DURATION);
            throw ValidationException::withMessages([
                'login_field' => trans('auth.failed'),
            ]);
        }

        // Authentication successful
        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login_field' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('login_field') . '|' . $this->ip()));
    }
}
