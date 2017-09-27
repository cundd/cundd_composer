<?php

namespace Cundd\CunddComposer\Tests\Unit;

use Cundd\CunddComposer\Process;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    /**
     * @test
     */
    public function executeTest()
    {
        $process = new Process('bash', [__DIR__ . '/../Resources/test-script.sh']);
        $this->assertSame("Hello\nWorld\n", $process->execute());
    }

    /**
     * @test
     */
    public function executeWithCallbackTest()
    {
        $invocationCounter = 0;
        $callback = function () use (&$invocationCounter) {
            $invocationCounter += 1;
        };
        $process = new Process('bash', [__DIR__ . '/../Resources/test-script.sh']);
        $this->assertSame("Hello\nWorld\n", $process->execute($callback));
        $this->assertTrue($invocationCounter > 2);
    }
}
