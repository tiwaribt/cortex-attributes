<?php

declare(strict_types=1);

namespace Cortex\Attributes\Console\Commands;

use Rinvex\Attributes\Console\Commands\RollbackCommand as BaseRollbackCommand;

class RollbackCommand extends BaseRollbackCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cortex:rollback:attributes {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback Cortex Attributes Tables.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (file_exists($path = 'database/migrations/cortex/attributes')) {
            $this->call('migrate:reset', [
                '--path' => $path,
                '--force' => $this->option('force'),
            ]);
        } else {
            $this->warn('No migrations found! Consider publish them first: <fg=green>php artisan cortex:publish:attributes</>');
        }

        parent::handle();
    }
}
