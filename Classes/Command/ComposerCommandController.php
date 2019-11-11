<?php

namespace Cundd\CunddComposer\Command;

use Cundd\CunddComposer\Process\ComposerProcess;
use Cundd\CunddComposer\Utility\ConfigurationUtility as ConfigurationUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Command to compile, watch and start LiveReload
 */
class ComposerCommandController extends CommandController
{
    /**
     * The escape character
     */
    const ANSI_ESCAPE = "\033";

    /**
     * Normal
     */
    const ANSI_COLOR_NORMAL = "[0m";

    /**
     * Color red
     */
    const ANSI_COLOR_RED = "[0;31m";

    /**
     * Package repository
     *
     * @var \Cundd\CunddComposer\Domain\Repository\PackageRepository
     * @inject
     */
    protected $packageRepository;

    /**
     * Asset installer
     *
     * @var \Cundd\CunddComposer\Installer\AssetInstaller
     * @inject
     */
    protected $assetInstaller;

    /**
     * Composer installer
     *
     * @var \Cundd\CunddComposer\Installer\ComposerInstaller
     * @inject
     */
    protected $composerInstaller;

    /**
     * Definition writer
     *
     * @var \Cundd\CunddComposer\Definition\Writer
     * @inject
     */
    protected $definitionWriter;

    /**
     * Settings
     *
     * @var array
     */
    protected $settings;

    /**
     * Installs the project dependencies from the composer.lock file if present, or falls back on the composer.json
     *
     * @param bool $noDev Disables installation of require-dev packages
     * @param bool $v     Increase the verbosity: 1 for normal output
     * @param bool $vv    Increase the verbosity: 2 for more verbose output
     * @param bool $vvv   Increase the verbosity: 3 for debug
     * @return void
     */
    public function installCommand($noDev = false, $v = false, $vv = false, $vvv = false)
    {
        $this->assertPHPExecutable();
        $this->definitionWriter->setIncludeDevelopmentDependencies(!$noDev);
        $this->definitionWriter->writeMergedComposerJson();

        $this->printLine('INSTALLING COMPOSER DEPENDENCIES');
        $this->printLine('This may take a while...');
        $this->printLine();
        $this->composerInstaller->install([$this, 'printStreamingOutput'], $this->combineVerbosity($v, $vv, $vvv));
        $this->printLine();

        $this->installAssets();

        $this->sendAndExit();
    }

    /**
     * Updates your dependencies to the latest version according to composer.json, and updates the composer.lock file
     *
     * @param bool $noDev Disables installation of require-dev packages
     * @param bool $v     Increase the verbosity: 1 for normal output
     * @param bool $vv    Increase the verbosity: 2 for more verbose output
     * @param bool $vvv   Increase the verbosity: 3 for debug
     * @return void
     */
    public function updateCommand($noDev = false, $v = false, $vv = false, $vvv = false)
    {
        $this->assertPHPExecutable();
        $this->definitionWriter->setIncludeDevelopmentDependencies(!$noDev);
        $this->definitionWriter->writeMergedComposerJson();

        $this->printLine('UPDATING COMPOSER DEPENDENCIES');
        $this->printLine('This may take a while...');
        $this->printLine();
        $this->composerInstaller->update([$this, 'printStreamingOutput'], $this->combineVerbosity($v, $vv, $vvv));
        $this->printLine();

        $this->installAssets();

        $this->sendAndExit();
    }

    /**
     * Install available assets
     *
     * @return void
     */
    public function installAssetsCommand()
    {
        if (!ConfigurationUtility::getConfiguration('allowInstallAssets')) {
            throw new \UnexpectedValueException('Asset installation is disabled', 1431008369);
        }
        $this->installAssets();
        $this->sendAndExit();
    }

    /**
     * List information about the required packages
     *
     * @return void
     */
    public function listCommand()
    {
        $this->assertPHPExecutable();

        $packages = $this->packageRepository->findAll();
        foreach ($packages as $package) {
            $required = array_filter(array_map('trim', explode("\n", $package->getRequire())));
            $requiredDev = array_filter(array_map('trim', explode("\n", $package->getRequireDev())));
            $output = [
                sprintf('%s [%s]: %s', $package->getName(), $package->getVersion(), $package->getDescription()),
                sprintf('  require:%s%s', PHP_EOL, '    ' . implode(PHP_EOL . '    ', $required)),
                sprintf('  require dev:%s%s', PHP_EOL, '    ' . implode(PHP_EOL . '    ', $requiredDev)),
                '',
            ];
            $this->output(implode(PHP_EOL, $output) . PHP_EOL . PHP_EOL);
        }
        $this->sendAndExit();
    }

    /**
     * Writes the merged composer.json
     *
     * @param boolean $noDev Disables installation of require-dev packages
     * @return void
     */
    public function writeComposerJsonCommand($noDev = false)
    {
        $this->assertPHPExecutable();
        $this->definitionWriter->setIncludeDevelopmentDependencies(!$noDev);
        if ($this->definitionWriter->writeMergedComposerJson()) {
            $this->outputLine(
                'Wrote merged composer definitions to "%s"',
                [$this->definitionWriter->getDestinationFilePath()]
            );
        } else {
            echo self::ANSI_ESCAPE . self::ANSI_COLOR_RED;
            printf(
                'Could not write merged composer definitions to "%s"' . PHP_EOL,
                $this->definitionWriter->getDestinationFilePath()
            );
            echo self::ANSI_ESCAPE . self::ANSI_COLOR_NORMAL;
        }

        $this->sendAndExit();
    }

    /**
     * Execute a composer command
     *
     * @param string $command Composer command to execute
     */
    public function execCommand($command)
    {
        $this->assertPHPExecutable();

        $composerProcess = new ComposerProcess([$this, 'printStreamingOutput']);
        $composerProcess->execute($command, $this->collectAdditionalOptions($command));
    }

    /**
     * @param $received
     */
    public function printStreamingOutput($received)
    {
        fwrite(STDOUT, (string)$received);
    }

    /**
     * @param string   $message
     * @param string[] ...$arguments
     */
    private function printLine($message = '', ...$arguments)
    {
        fwrite(STDOUT, vsprintf((string)$message, $arguments) . PHP_EOL);
    }

    /**
     * Invoked after the install/update action
     *
     * @return void
     */
    public function installAssets()
    {
        if (
            ConfigurationUtility::getConfiguration('automaticallyInstallAssets')
            && ConfigurationUtility::getConfiguration('allowInstallAssets')
        ) {
            $this->assetInstaller->setAssetPaths(ConfigurationUtility::getConfiguration('assetPaths'));
            $installedAssets = $this->assetInstaller->installAssets();

            if (count($installedAssets) > 0) {
                $this->outputLine('INSTALLED ASSETS:');
                foreach ($installedAssets as $asset) {
                    $this->outputLine(
                        sprintf(
                            '%s [%s]: %s',
                            $asset['name'],
                            $asset['version'],
                            $asset['assetKey']
                        )
                    );
                }
            } else {
                $this->outputLine('No assets found to install');
            }
        }
    }

    /**
     * Check if the PHP executable can be found
     */
    protected function assertPHPExecutable()
    {
        if (!ConfigurationUtility::getPHPExecutable()) {
            $this->outputLine('ERROR: PHP executable could not be found');
            throw new \UnexpectedValueException('PHP executable could not be found', 1431007408);
        }
    }

    private function combineVerbosity($v, $vv, $vvv)
    {
        if ($vvv) {
            return '-vvv';
        }
        if ($vv) {
            return '-vv';
        }
        if ($v) {
            return '-v';
        }

        return '';
    }

    private function collectAdditionalOptions($command)
    {
        global $argv;
        $additionalOptions = $argv;
        while (!empty($additionalOptions) && $additionalOptions[0] !== $command) {
            array_shift($additionalOptions);
        }
        array_shift($additionalOptions);

        return $additionalOptions;
    }
}
