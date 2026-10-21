<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Issues a token for the API.
 *
 * The reading of the gallery is open, the writing asks for a token. The
 * command creates the user when it does not exist yet, so a fresh
 * installation can be used right away.
 */
class IssueApiToken extends Command
{
    /**
     * The name and the signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:token {email : The address of the user} {--name=api : The name of the token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue a token for the API of the gallery';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('The address is not a valid email address.');

            return self::FAILURE;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $email, 'password' => bcrypt(bin2hex(random_bytes(16)))]
        );

        $token = $user->createToken((string) $this->option('name'));

        $this->info('Token for '.$email.':');
        $this->line($token->plainTextToken);
        $this->comment('Send it in the header: Authorization: Bearer <token>');

        return self::SUCCESS;
    }
}
