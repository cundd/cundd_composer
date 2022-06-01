<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Command;

use Cundd\CunddComposer\Process\ComposerProcess;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_shift;

class ExecCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Execute a composer command')
            ->setHelp(
                'Execute an arbitrary composer command'
            )
            ->addArgument(
                'arguments',
                InputArgument::IS_ARRAY,
                'Additional arguments or options to forward to composer'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->assertPHPExecutable($output);
        $composerProcess = new ComposerProcess([$this, 'printStreamingOutput']);

        $arguments = $input->getArgument('arguments');
        $command = array_shift($arguments);
        if ($command) {
            $composerProcess->execute($command, $arguments);

            return 0;
        } else {
            $output->writeln('<error>Missing argument \'command\'</error>');

            return 1;
        }
    }
}
