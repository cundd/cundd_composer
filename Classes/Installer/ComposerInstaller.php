<?php

namespace Cundd\CunddComposer\Installer;

use Cundd\CunddComposer\Process;

class ComposerInstaller
{
    /**
     * Call composer on the command line to install the dependencies.
     *
     * @param callable|null $receivedContent A callback that will be invoked when script output is received
     * @param string        $verbosity       Increase the verbosity: 'v' for normal output, 'vv' for more verbose output and 'vvv' for debug
     * @return string Returns the composer output
     */
    public function install(callable $receivedContent = null, $verbosity = '')
    {
        return $this->executeComposerCommand(
            'install',
            $receivedContent ?: function () {
            },
            $verbosity
        );
    }

    /**
     * Call composer on the command line to update the dependencies.
     *
     * @param callable|null $receivedContent A callback that will be invoked when script output is received
     * @param string        $verbosity       Increase the verbosity: 'v' for normal output, 'vv' for more verbose output and 'vvv' for debug
     * @return string Returns the composer output
     */
    public function update(callable $receivedContent = null, $verbosity = '')
    {
        return $this->executeComposerCommand(
            'update',
            $receivedContent ?: function () {
            },
            $verbosity
        );
    }

    /**
     * Execute the given composer command
     *
     * @param string   $command         The composer command to execute
     * @param callable $receivedContent A callback that will be invoked when script output is received
     * @param string   $verbosity       Increase the verbosity: 'v' for normal output, 'vv' for more verbose output and 'vvv' for debug
     * @return string Returns the composer output
     */
    protected function executeComposerCommand($command, callable $receivedContent, $verbosity)
    {
        $arguments = [];
        if ($verbosity) {
            $arguments[] = (string)$verbosity;
        }

        $process = new Process\ComposerProcess($receivedContent);

        return $process->execute(
            $command,
            [
                // '--no-ansi',
                // '--profile',
                // '--prefer-dist',
                '--optimize-autoloader',
            ]
        );
    }
}
