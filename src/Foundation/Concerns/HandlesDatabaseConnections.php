<?php

namespace Orchestra\Testbench\Foundation\Concerns;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Orchestra\Testbench\Foundation\Env;

trait HandlesDatabaseConnections
{
    /**
     * Allow to use database connections environment variables.
     */
    final protected function usesDatabaseConnectionsEnvironmentVariables(Repository $config, string $driver, string $keyword): void
    {
        $keyword = Str::upper($keyword);

        $options = [
            'url' => ['env' => 'URL', 'rules' => function ($value) {
                return ! empty($value) && \is_string($value);
            }],
            'host' => ['env' => 'HOST', 'rules' => function ($value) {
                return ! empty($value) && \is_string($value);
            }],
            'port' => ['env' => 'PORT', 'rules' => function ($value) {
                return ! empty($value) && \is_int($value);
            }],
            'database' => ['env' => ['DB', 'DATABASE'], 'rules' => function ($value) {
                return ! empty($value) && \is_string($value);
            }],
            'username' => ['env' => ['USER', 'USERNAME'], 'rules' => function ($value) {
                return ! empty($value) && \is_string($value);
            }],
            'password' => ['env' => 'PASSWORD', 'rules' => function ($value) {
                return \is_null($value) || \is_string($value);
            }],
            'collation' => ['env' => 'COLLATION', 'rules' => function ($value) {
                return \is_null($value) || \is_string($value);
            }],
        ];

        $config->set(
            Collection::make($options)
                ->when($driver === 'pgsql', static function ($options) {
                    return $options->put('schema', ['env' => 'SCHEMA', 'rules' => function ($value) {
                        return ! empty($value) && \is_string($value);
                    }]);
                })
                ->mapWithKeys(static function ($options, $key) use ($driver, $keyword, $config) {
                    $name = "database.connections.{$driver}.{$key}";

                    /** @var mixed $configuration */
                    $configuration = Collection::make(Arr::wrap($options['env']))
                        ->transform(static function ($value) use ($keyword) {
                            return Env::get("{$keyword}_{$value}");
                        })->first($options['rules'] ?? static function ($value) {
                            return ! ($value);
                        }) ?? $config->get($name);

                    return [
                        "{$name}" => $configuration,
                    ];
                })->all()
        );
    }
}
