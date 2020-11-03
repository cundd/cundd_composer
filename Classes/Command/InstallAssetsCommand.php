<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Command;

use Cundd\CunddComposer\Utility\ConfigurationUtility as ConfigurationUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallAssetsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Install available assets')
            ->setHelp('Install available assets');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (ConfigurationUtility::getConfiguration('allowInstallAssets')) {
            $this->installAssets($output);

            return 0;
        } else {
            $output->writeln('Asset installation is disabled in configuration');

            return 1;
        }
    }
}
