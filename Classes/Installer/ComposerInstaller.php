<?php

namespace Cundd\CunddComposer\Installer;

use Cundd\CunddComposer\Utility\ConfigurationUtility as ConfigurationUtility;
use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;

class ComposerInstaller
{
    /**
     * Call composer on the command line to install the dependencies.
     *
     * @param callable|null $receivedContent A callback that will be invoked when script output is received
     * @return string Returns the composer output
     */
    public function install(callable $receivedContent = null)
    {
        return $this->executeComposerCommand(
            'install',
            $receivedContent ?: function () {
            }
        );
    }

    /**
     * Call composer on the command line to update the dependencies.
     *
     * @param callable|null $receivedContent A callback that will be invoked when script output is received
     * @return string Returns the composer output
     */
    public function update(callable $receivedContent = null)
    {
        return $this->executeComposerCommand(
            'update',
            $receivedContent ?: function () {
            }
        );
    }

    /**
     * Execute the given composer command
     *
     * @param string   $command         The composer command to execute
     * @param callable $receivedContent A callback that will be invoked when script output is received
     * @return string Returns the composer output
     */
    protected function executeComposerCommand($command, callable $receivedContent)
    {
        $pathToComposer = ComposerGeneralUtility::getComposerPath();

        ComposerGeneralUtility::makeSureTempPathExists();
        $fullCommand = [
            ConfigurationUtility::getPHPExecutable(),
            $pathToComposer,
            $command,
            '--working-dir',
            ComposerGeneralUtility::getTempPath(),
            '--no-interaction',
//			 '--no-ansi',
            '--verbose',
//			 '--profile',
//            '--prefer-dist',
            '--optimize-autoloader',
        ];

        $output = $this->executeShellCommand(
            implode(' ', array_map('escapeshellarg', $fullCommand)),
            $receivedContent,
            $this->getEnvironmentVariables()
        );

        return $output;
    }

    /**
     * Execute the shell command
     *
     * @param string   $fullCommand     Full composer command
     * @param callable $receivedContent A callback that will be invoked when script output is received
     * @param array    $environmentVariables
     * @return string
     */
    protected function executeShellCommand($fullCommand, callable $receivedContent, array $environmentVariables)
    {
        $output = '';
        $descriptorSpec = [
            0 => ['pipe', 'r'], // stdin is a pipe that the child will read from
            1 => ['pipe', 'w'], // stdout is a pipe that the child will write to
            2 => ['pipe', 'w'], // stderr is a pipe that the child will write to
        ];

        $cwd = ComposerGeneralUtility::getTempPath();

        $process = proc_open(
            $fullCommand,
            $descriptorSpec,
            $pipes,
            $cwd,
            $environmentVariables
        );

        if (is_resource($process)) {
            $this->read($process, $pipes, $receivedContent);

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            if ($this->processIsRunning($process)) {
                proc_terminate($process);
            }
            proc_close($process);
        }

        return $output;
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

    /**
     * @param resource   $process
     * @param resource[] $pipes
     * @param callable   $receivedContent
     * @return string
     */
    private function read($process, array $pipes, callable $receivedContent)
    {
        $outputPipe = $pipes[1];
        $errorPipe = $pipes[2];
        stream_set_blocking($outputPipe, false);
        stream_set_blocking($errorPipe, false);

        $readStreams = [
            $outputPipe,
            $errorPipe,
        ];

        $output = '';
        do {
            $tmp = null;
            $ready = stream_select($readStreams, $writeStreams, $exceptStreams, 200000, 50000);

            if ($ready === false) {
                // something went wrong!!
                break;
            } elseif ($ready > 0) {
                foreach ($readStreams as $stream) {
                    $received = stream_get_contents($stream);
                    $output .= $received;

                    $receivedContent($received, $output);

                    if (feof($stream)) {
                        break;
                    }
                }
            }

            if (!$this->processIsRunning($process)) {
                break;
            }
        } while (true);

        return $output;
    }

    /**
     * @param $process
     * @return bool
     */
    private function processIsRunning($process): bool
    {
        $state = proc_get_status($process);

        return $state && $state['running'];
    }
}
