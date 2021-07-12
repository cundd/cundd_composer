<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Command;

use Cundd\CunddComposer\Definition\Writer;
use Cundd\CunddComposer\Installer\AssetInstaller;
use Cundd\CunddComposer\Installer\ComposerInstaller;
use Cundd\CunddComposer\Utility\ConfigurationUtility as ConfigurationUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use UnexpectedValueException;
use function array_shift;
use function count;
use function fwrite;
use function sprintf;
use function vsprintf;

abstract class AbstractCommand extends Command
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @return ObjectManagerInterface
     */
    public function getObjectManager(): ObjectManagerInterface
    {
        if (!$this->objectManager) {
            $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        }

        return $this->objectManager;
    }

    /**
     * @return Writer
     */
    public function getDefinitionWriter(): Writer
    {
        return $this->getObjectManager()->get(Writer::class);
    }

    /**
     * @return ComposerInstaller
     */
    public function getComposerInstaller(): ComposerInstaller
    {
        return $this->getObjectManager()->get(ComposerInstaller::class);
    }

    /**
     * @param $received
     */
    public function printStreamingOutput($received)
    {
        fwrite(STDOUT, (string)$received);
    }

    /**
     * @param OutputInterface $output
     * @param string          $message
     * @param string[]        ...$arguments
     */
    protected function printLine(OutputInterface $output, string $message = '', ...$arguments)
    {
        $output->writeln(vsprintf($message, $arguments));
    }

    protected function postInstallOrUpdateAction(OutputInterface $output)
    {
        if (
            ConfigurationUtility::getConfiguration('automaticallyInstallAssets')
            && ConfigurationUtility::getConfiguration('allowInstallAssets')
        ) {
            $this->installAssets($output);
        }
    }

    /**
     * Invoked after the install/update action
     *
     * @param OutputInterface $output
     * @return void
     */
    protected function installAssets(OutputInterface $output)
    {
        if (ConfigurationUtility::getConfiguration('allowInstallAssets')) {
            $assetInstaller = $this->getObjectManager()->get(AssetInstaller::class);
            $assetInstaller->setAssetPaths(ConfigurationUtility::getConfiguration('assetPaths'));
            $installedAssets = $assetInstaller->installAssets();

            if (count($installedAssets) > 0) {
                $output->writeln('INSTALLED ASSETS:');
                foreach ($installedAssets as $asset) {
                    $output->writeln(
                        sprintf(
                            '%s [%s]: %s',
                            $asset['name'],
                            $asset['version'],
                            $asset['assetKey']
                        )
                    );
                }
            } else {
                $output->writeln('No assets found to install');
            }
        }
    }

    /**
     * Check if the PHP executable can be found
     *
     * @param OutputInterface $output
     */
    protected function assertPHPExecutable(OutputInterface $output)
    {
        if (!ConfigurationUtility::getPHPExecutable()) {
            $output->writeln('ERROR: PHP executable could not be found');
            throw new UnexpectedValueException('PHP executable could not be found', 1431007408);
        }
    }

    protected function combineVerbosity(int $verbosity): string
    {
        switch ($verbosity) {
            case OutputInterface::VERBOSITY_VERBOSE:
                return '-v';
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                return '-vv';
            case OutputInterface::VERBOSITY_DEBUG:
                return '-vvv';
            case OutputInterface::VERBOSITY_NORMAL:
            case OutputInterface::VERBOSITY_QUIET;
            default:
                return '';
        }
    }

    protected function collectAdditionalOptions($command): array
    {
        global $argv;
        $additionalOptions = $argv;
        while (!empty($additionalOptions) && $additionalOptions[0] !== $command && $additionalOptions[0] !== '--') {
            array_shift($additionalOptions);
        }
        array_shift($additionalOptions);

        return $additionalOptions;
    }
}
