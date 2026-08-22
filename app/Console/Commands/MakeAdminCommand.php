<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Creates an account that can manage the wishlist.
 *
 * Public registration is switched off in config/fortify.php and stays that way, so this is
 * the only route to an account. Nobody can sign themselves up from the web.
 */
#[Signature('make:admin {email : The email address to sign in with}
                        {password : The password for the new account}
                        {--name= : Display name, defaulting to the email address}
                        {--force : Reset the password if the account already exists}')]
#[Description('Create an admin who can manage the wishlist')]
class MakeAdminCommand extends Command
{
    use PasswordValidationRules;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $email */
        $email = $this->argument('email');
        /** @var string $password */
        $password = $this->argument('password');

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null && ! $this->option('force')) {
            $this->components->error(__('An account already exists for :email.', ['email' => $email]));
            $this->components->bulletList([
                __('Pass --force to reset that account\'s password instead.'),
            ]);

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'string', 'email', 'max:255'],
                // Reuses the application's own password policy so console-made accounts
                // are held to exactly the same standard as any other. Only "confirmed" is
                // dropped, since there is no second field to confirm against on the CLI.
                'password' => $this->consolePasswordRules(),
            ],
        );

        if ($validator->fails()) {
            $this->components->error(__('That will not do:'));
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $name = $this->resolveName($email);

        if ($existing !== null) {
            $existing->update(['password' => $password]);

            $this->components->info(__('Password reset for :email.', ['email' => $email]));

            return self::SUCCESS;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->components->info(__('Admin :name created. Sign in at /login.', ['name' => $name]));

        return self::SUCCESS;
    }

    /**
     * The application's password policy, minus the confirmation rule.
     *
     * @return array<int, ValidationRule|Password|array<mixed>|string>
     */
    private function consolePasswordRules(): array
    {
        return array_values(array_filter(
            $this->passwordRules(),
            fn (mixed $rule): bool => $rule !== 'confirmed',
        ));
    }

    /**
     * Work out a display name, falling back to a tidied-up version of the email address.
     */
    private function resolveName(string $email): string
    {
        $name = $this->option('name');

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        return Str::of($email)
            ->before('@')
            ->replace(['.', '_', '-'], ' ')
            ->squish()
            ->title()
            ->value();
    }
}
