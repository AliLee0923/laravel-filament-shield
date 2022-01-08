<?php

namespace BezhanSalleh\FilamentShield\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class MakeInstallShieldCommand extends Command
{
    public $signature = 'shield:install
        {--F|fresh}
        {--O|only : Generate permissions and/or policies `Only` for entities listed in config.}
        {--A|all : Generate permissions and/or policies for all `Resources`, `Pages` and `Widgets`.}
    ';

    public $description = "One Command to Rule them All 🔥";

    public function handle(): int
    {
        $this->alert('Following operations will be performed:');
        $this->info('-  Publishes core package config');
        $this->info('-  Publishes core package migration');
        $this->warn('   - On fresh applications database will be migrated');
        $this->warn('   - You can also force this behavior by supplying the --fresh option');
        $this->info('-  Creates a filament user');
        $this->warn('   - Assigns Super Admin role if enabled in config');
        $this->warn('   - And/Or Assigns Filament User role if enabled in config');
        $this->info('-  Discovers filament resources and generates Permissions and Policies accordingly');
        $this->warn('   - Will override any existing policies if available');
        $this->info('- Publishes Shield Resource');

        $confirmed = $this->confirm('Do you wish to continue?', true);

        if ($this->CheckIfAlreadyInstalled() && ! $this->option('fresh')) {
            $this->error('Core package(`spatie/laravel-permission`) is already installed!');
            $this->comment('You should run `shield:generate` instead');

            return self::INVALID;
        }

        if ($confirmed) {
            $this->call('vendor:publish', [
                '--provider' => 'Spatie\Permission\PermissionServiceProvider',
            ]);
            $this->info('Core Package config published.');

            if ($this->option('fresh')) {
                $this->call('migrate:fresh');
                $this->info('Database migrations freshed up.');
            } else {
                $this->call('migrate');
                $this->info('Database migrated.');
            }

            $this->info('Creating Super Admin...');
            $this->call('shield:super-admin');
            $this->call('shield:publish');

            if ($this->option('only')) {
                $output = Artisan::call('shield:generate --only');
                if ($output === 2) {
                    $this->comment('Seems like you have not enabled your `only` config properly!');
                }
            }else if ($this->option('all')) {

                $this->call('shield:generate');

            }else{

                Artisan::call('shield:generate --except');

            }


            $this->info('Filament Shield🛡 is now active ✅');
        } else {
            $this->comment('`shield:install` command was cancelled.');
        }

        if ($this->confirm('Would you like to show some love by starring the repo?', true)) {
            if (PHP_OS_FAMILY === 'Darwin') {
                exec('open https://github.com/bezhanSalleh/filament-shield');
            }
            if (PHP_OS_FAMILY === 'Linux') {
                exec('xdg-open https://github.com/bezhanSalleh/filament-shield');
            }
            if (PHP_OS_FAMILY === 'Windows') {
                exec('start https://github.com/bezhanSalleh/filament-shield');
            }

            $this->line('Thank you!');
        }

        return self::SUCCESS;
    }

    protected function CheckIfAlreadyInstalled(): bool
    {
        $count = collect(['permissions','roles','role_has_permissions','model_has_roles','model_has_permissions'])
                ->filter(function ($table) {
                    return Schema::hasTable($table);
                })
                ->count();
        if ($count !== 0) {
            return true;
        }

        return false;
    }
}
