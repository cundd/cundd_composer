<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;

class WriteComposerJsonCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Write merged composer.json')
            ->setHelp('Write the merged composer.json')
            ->addOption('no-dev', null, InputOption::VALUE_NONE, 'Disable installation of require-dev packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assertPHPExecutable($output);

        $writer = $this->getDefinitionWriter();
        $writer->setIncludeDevelopmentDependencies(!$input->getOption('no-dev'));
        if ($writer->writeMergedComposerJson()) {
            $output->writeln(
                sprintf(
                    '<info>Wrote merged composer definitions to "%s"</info>',
                    $writer->getDestinationFilePath()
                )
            );

            return 0;
        } else {
            $output->writeln(
                sprintf(
                    '<error>Could not write merged composer definitions to "%s"</error>',
                    $writer->getDestinationFilePath()
                )
            );

            return 1;
        }
    }
}
