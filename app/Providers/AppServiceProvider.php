<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureMailBranding();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Model::shouldBeStrict(! app()->isProduction());

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        // SMS one-time codes are expensive and abusable, so they are metered twice:
        // per IP here, and per phone number inside PhoneVerificationService.
        RateLimiter::for('sms-otp', fn (Request $request): Limit => Limit::perMinutes(10, 6)->by($request->ip()));
        RateLimiter::for('sms-otp-confirm', fn (Request $request): Limit => Limit::perMinutes(10, 12)->by($request->ip()));

        // BookTrips password policy: 8+ characters with upper, lower, number and symbol.
        Password::defaults(fn (): Password => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols());
    }

    /**
     * Super admins pass every authorization check; policies still apply to everyone else.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isAdmin() ? true : null;
        });
    }

    /**
     * Use the branded BookTrips email templates for verification and password reset.
     */
    protected function configureMailBranding(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Verify your BookTrips email')
                ->view('mail.verify-email', [
                    'name' => $notifiable instanceof User ? $notifiable->name : '',
                    'url' => $url,
                    'expiresIn' => config('auth.verification.expire', 60).' minutes',
                ]);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $email = $notifiable instanceof User ? $notifiable->getEmailForPasswordReset() : '';

            return (new MailMessage)
                ->subject('Reset your BookTrips password')
                ->view('mail.reset-password', [
                    'name' => $notifiable instanceof User ? $notifiable->name : '',
                    'url' => route('password.reset', [
                        'token' => $token,
                        'email' => $email,
                    ]),
                ]);
        });
    }
}
