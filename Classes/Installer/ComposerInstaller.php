<?php

namespace Cundd\CunddComposer\Installer;

use Cundd\CunddComposer\Process;
use Cundd\CunddComposer\Utility\ConfigurationUtility as ConfigurationUtility;
use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;

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
        $pathToComposer = ComposerGeneralUtility::getComposerPath();

        ComposerGeneralUtility::makeSureTempPathExists();
        $arguments = [
            $pathToComposer,
            $command,
            '--working-dir',
            ComposerGeneralUtility::getTempPath(),
            '--no-interaction',
//			 '--no-ansi',
//			 '--profile',
//            '--prefer-dist',
            '--optimize-autoloader',
        ];

        if ($verbosity) {
            $arguments[] = (string)$verbosity;
        }
        $process = new Process(ConfigurationUtility::getPHPExecutable(), $arguments, $this->getEnvironmentVariables());

        return $process->execute($receivedContent);
    }

    /**
     * @return array
     */
    protected function getEnvironmentVariables()
    {
        // Some environment variable names that are forwarded to composer
        $environmentVariablesToInherit = [
            'COMPOSER',
            'COMPOSER_ROOT_VERSION',
            'COMPOSER_VENDOR_DIR',
            'COMPOSER_BIN_DIR',
            'http_proxy',
            'https_proxy',
            'ftp_proxy',
            'HTTP_PROXY',
            'no_proxy',
            'HTTP_PROXY_REQUEST_FULLURI',
            'COMPOSER_CACHE_DIR',
            'COMPOSER_PROCESS_TIMEOUT',
            'COMPOSER_CAFILE',
            'COMPOSER_AUTH',
            'COMPOSER_DISCARD_CHANGES',
            'COMPOSER_NO_INTERACTION',
            'COMPOSER_ALLOW_SUPERUSER',
            'COMPOSER_MIRROR_PATH_REPOS',

            'PATH',
        ];

        $environment = array_merge(
            $_ENV,
            [
                'COMPOSER_HOME' => ComposerGeneralUtility::getTempPath(),
            ]
        );

        foreach ($environmentVariablesToInherit as $variable) {
            $value = getenv($variable);
            if (false !== $value) {
                $environment[$variable] = $value;
            }
        }

        return $environment;
    }
}
