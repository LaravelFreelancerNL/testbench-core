<?php

namespace Orchestra\Testbench\Foundation;

use Composer\InstalledVersions;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand as CollisionTestCommand;
use Spatie\Ray\Settings\Settings;

class TestbenchServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $workingPath = defined('TESTBENCH_WORKING_PATH') ? TESTBENCH_WORKING_PATH : null;

        AboutCommand::add('Testbench', fn () => [
            'Core Version' => class_exists(InstalledVersions::class) ? InstalledVersions::getPrettyVersion('orchestra/testbench-core') : '<fg=yellow;options=bold>-</>',
            'Skeleton Path' => str_replace($workingPath, '', $this->app->basePath()),
        ]);

        $this->callAfterResolving(Settings::class, function ($settings, $app) {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            $config = $app->make('config');

            if ($config->get('database.default') === 'sqlite' && ! file_exists($config->get('database.connections.sqlite.database'))) {
                $config->set('database.default', 'testing');

                $settings->send_queries_to_ray = false;
                $settings->send_duplicate_queries_to_ray = false;
                $settings->send_slow_queries_to_ray = false;
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (file_exists($this->app->basePath('migrations'))) {
            $this->loadMigrationsFrom($this->app->basePath('migrations'));
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                $this->isCollisionDependenciesInstalled()
                    ? Console\TestCommand::class
                    : Console\TestFallbackCommand::class,
                Console\CreateSqliteDbCommand::class,
                Console\DropSqliteDbCommand::class,
                Console\DevToolCommand::class,
                Console\ServeCommand::class,
            ]);
        }
    }

    /**
     * Check if the parallel dependencies are installed.
     *
     * @return bool
     */
    protected function isCollisionDependenciesInstalled(): bool
    {
        return class_exists(CollisionTestCommand::class);
    }
}
