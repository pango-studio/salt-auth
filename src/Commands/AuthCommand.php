<?php

namespace Salt\Auth\Commands;

use Illuminate\Console\Command;

class AuthCommand extends Command
{
    public $signature = 'auth';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
