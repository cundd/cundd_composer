<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;
use const STDOUT;

class WriteComposerJsonCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Write merged composer.json')
            ->addArgument('output-path', InputArgument::OPTIONAL, 'Output path')
            ->addOption('no-dev', null, InputOption::VALUE_NONE, 'Disable installation of require-dev packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->assertPHPExecutable($output);

        $writer = $this->getDefinitionWriter();
        $writer->setIncludeDevelopmentDependencies(!$input->getOption('no-dev'));

        $outputPath = $input->getArgument('output-path') ??  $writer->getDefaultDestinationFilePath();

        // Write to STDOUT
        if ($outputPath === '-') {
            $writer->writeMergedComposerJson(STDOUT);

            return 0;
        }

        if ($writer->writeMergedComposerJson($outputPath)) {
            $output->writeln(
                sprintf(
                    '<info>Wrote merged composer definitions to "%s"</info>',
                    $outputPath
                )
            );

            return 0;
        }

        $output->writeln(
            sprintf(
                '<error>Could not write merged composer definitions to "%s"</error>',
                $outputPath
            )
        );

        return 1;
    }
}
