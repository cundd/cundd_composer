<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Command;

use Cundd\CunddComposer\Domain\Model\Package;
use Cundd\CunddComposer\Domain\Repository\PackageRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_filter;
use function array_map;
use function explode;
use function implode;
use function sprintf;

class ListCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setDescription('List information about the required packages')
            ->setHelp('List information about the required packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assertPHPExecutable($output);

        $packages = $this->getObjectManager()->get(PackageRepository::class)->findAll();
        /** @var Package $package */
        foreach ($packages as $package) {
            $required = $this->splitTrim($package->getRequire());
            $requiredDev = $this->splitTrim($package->getRequireDev());
            $info = [
                sprintf('%s [%s]: %s', $package->getName(), $package->getVersion(), $package->getDescription()),
                sprintf('  require:%s%s', PHP_EOL, '    ' . implode(PHP_EOL . '    ', $required)),
                sprintf('  require dev:%s%s', PHP_EOL, '    ' . implode(PHP_EOL . '    ', $requiredDev)),
                '',
            ];
            $output->writeln(implode(PHP_EOL, $info) . PHP_EOL . PHP_EOL);
        }

        return 0;
    }

    /**
     * @param string $require
     * @return array
     */
    protected function splitTrim(string $require): array
    {
        return array_filter(array_map('trim', explode("\n", $require)));
    }
}
