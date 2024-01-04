<?php

namespace Orchestra\Testbench\Bootstrap;

use Illuminate\Log\LogManager;
use Orchestra\Testbench\Exceptions\DeprecatedException;
use Orchestra\Testbench\Foundation\Env;

/**
 * @internal
 */
final class HandleExceptions extends \Illuminate\Foundation\Bootstrap\HandleExceptions
{
    /**
     * Create a new exception handler instance.
     *
     * @param  \PHPUnit\Framework\TestCase|null  $testbench
     */
    public function __construct(
        protected $testbench = null
    ) {
    }

    /**
     * Reports a deprecation to the "deprecations" logger.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return void
     *
     * @throws \Orchestra\Testbench\Exceptions\DeprecatedException
     */
    #[\Override]
    public function handleDeprecationError($message, $file, $line, $level = E_DEPRECATED)
    {
        parent::handleDeprecationError($message, $file, $line, $level);

        $testbenchConvertDeprecationsToExceptions = Env::get('TESTBENCH_CONVERT_DEPRECATIONS_TO_EXCEPTIONS', false);

        if ($testbenchConvertDeprecationsToExceptions === true) {
            throw new DeprecatedException($message, $level, $file, $line);
        }
    }

    /**
     * Ensure the "deprecations" logger is configured.
     *
     * @return void
     */
    #[\Override]
    protected function ensureDeprecationLoggerIsConfigured()
    {
        with(self::$app->make('config'), static function ($config) {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            if ($config->get('logging.channels.deprecations')) {
                return;
            }

            /** @var array{channel?: string, trace?: bool}|string|null $options */
            $options = $config->get('logging.deprecations');

            if (\is_array($options)) {
                $driver = $options['channel'] ?? 'null';
            } else {
                $driver = $options ?? 'null';
            }

            if ($driver === 'single') {
                $config->set('logging.channels.deprecations', array_merge($config->get('logging.channels.single'), [
                    'path' => self::$app->storagePath('logs/deprecations.log'),
                ]));
            } else {
                $config->set('logging.channels.deprecations', $config->get("logging.channels.{$driver}"));
            }

            $config->set('logging.deprecations', [
                'channel' => 'deprecations',
                'trace' => true,
            ]);
        });
    }

    /**
     * Determine if deprecation error should be ignored.
     *
     * @return bool
     */
    #[\Override]
    protected function shouldIgnoreDeprecationErrors()
    {
        return ! class_exists(LogManager::class)
            || ! self::$app->hasBeenBootstrapped()
            || ! Env::get('LOG_DEPRECATIONS_WHILE_TESTING', true);
    }

    /**
     * Flush the boostrap's global state.
     *
     * @return void
     */
    public static function flushState(): void
    {
        self::forgetApp();

        restore_error_handler();
        restore_exception_handler();
    }
}
