<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Process;

use Cundd\CunddComposer\Process;
use Cundd\CunddComposer\Utility\ConfigurationUtility as ConfigurationUtility;
use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;
use function array_merge;
use function getenv;

class ComposerProcess
{
    /**
     * A callback that will be invoked when script output is received
     *
     * @var callable
     */
    private $receivedContent;

    /**
     * ComposerProcess constructor.
     *
     * @param callable $receivedContent A callback that will be invoked when script output is received
     */
    public function __construct(callable $receivedContent)
    {
        $this->receivedContent = $receivedContent;
    }

    /**
     * Execute the given composer command
     *
     * @param string $command The composer command to execute
     * @param array  $arguments
     * @return string Returns the composer output
     */
    public function execute(string $command, array $arguments = []): string
    {
        ComposerGeneralUtility::makeSureTempPathExists();;
        $commandArguments = array_merge(
            [
                ComposerGeneralUtility::getComposerPath(),
                $command,
                '--working-dir',
                ComposerGeneralUtility::getTempPath(),
                '--no-interaction',
            ],
            $arguments
        );

        $process = new Process(
            ConfigurationUtility::getPHPExecutable(),
            $commandArguments,
            $this->getEnvironmentVariables()
        );

        return $process->execute($this->receivedContent);
    }

    /**
     * @return array
     */
    protected function getEnvironmentVariables(): array
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

            'PHP_INI_SCAN_DIR',

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
