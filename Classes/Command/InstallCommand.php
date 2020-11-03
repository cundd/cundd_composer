<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Install dependencies from composer.lock')
            ->setHelp(
                'Installs the project dependencies from the composer.lock file if present, or falls back on the composer.json'
            )
            ->addOption('no-dev', null, InputOption::VALUE_NONE, 'Disable installation of require-dev packages')
            ->addArgument(
                'additional-arguments',
                InputArgument::OPTIONAL,
                'Additional arguments or options to forward to composer'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assertPHPExecutable($output);
        $writer = $this->getDefinitionWriter();
        $writer->setIncludeDevelopmentDependencies(!$input->getOption('no-dev'));
        $writer->writeMergedComposerJson();

        $this->printLine($output, 'INSTALLING COMPOSER DEPENDENCIES');
        $this->printLine($output, 'This may take a while...');
        $this->printLine($output);

        $this->getComposerInstaller()->install(
            [$this, 'printStreamingOutput'],
            $this->combineVerbosity($output->getVerbosity()),
            $this->collectAdditionalOptions('install')
        );
        $this->printLine($output);

        $this->postInstallOrUpdateAction($output);

        return 0;
    }
}
