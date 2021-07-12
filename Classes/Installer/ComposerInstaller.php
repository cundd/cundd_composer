<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Installer;

use Cundd\CunddComposer\Process;
use function array_merge;

class ComposerInstaller
{
    /**
     * Call composer on the command line to install the dependencies.
     *
     * @param callable|null $receivedContent     A callback that will be invoked when script output is received
     * @param string        $verbosity           Increase the verbosity: 'v' for normal output, 'vv' for more verbose output and 'vvv' for debug
     * @param array         $additionalArguments Additional arguments to pass to Composer
     * @return string Returns the composer output
     */
    public function install(callable $receivedContent = null, string $verbosity = '', array $additionalArguments = []): string
    {
        return $this->executeComposerCommand(
            'install',
            $receivedContent ?: function () {
            },
            $verbosity,
            $additionalArguments
        );
    }

    /**
     * Call composer on the command line to update the dependencies.
     *
     * @param callable|null $receivedContent     A callback that will be invoked when script output is received
     * @param string        $verbosity           Increase the verbosity: 'v' for normal output, 'vv' for more verbose output and 'vvv' for debug
     * @param array         $additionalArguments Additional arguments to pass to Composer
     * @return string Returns the composer output
     */
    public function update(callable $receivedContent = null, string $verbosity = '', array $additionalArguments = []): string
    {
        return $this->executeComposerCommand(
            'update',
            $receivedContent ?: function () {
            },
            $verbosity,
            $additionalArguments
        );
    }

    /**
     * Execute the given composer command
     *
     * @param string   $command             The composer command to execute
     * @param callable $receivedContent     A callback that will be invoked when script output is received
     * @param string   $verbosity           Increase the verbosity: 'v' for normal output, 'vv' for more verbose output and 'vvv' for debug
     * @param array    $additionalArguments Additional arguments to pass to Composer
     * @return string Returns the composer output
     */
    protected function executeComposerCommand(
        string $command,
        callable $receivedContent,
        string $verbosity,
        array $additionalArguments
    ): string {
        $arguments = [];
        if ($verbosity) {
            $arguments[] = (string)$verbosity;
        }

        $process = new Process\ComposerProcess($receivedContent);

        return $process->execute(
            $command,
            array_merge(
                [
                    // '--no-ansi',
                    // '--profile',
                    // '--prefer-dist',
                    '--optimize-autoloader',
                ],
                $additionalArguments
            )
        );
    }
}
