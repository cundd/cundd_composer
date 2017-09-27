<?php

namespace Cundd\CunddComposer;

use Cundd\CunddComposer\Exception\ProcessException;

/**
 * Process class to wrap invocations of external scripts
 *
 * Highly inspired by [Symfony Process](https://symfony.com/doc/current/components/process.html)
 */
class Process
{
    const STATE_READY = 'ready';
    const STATE_RUNNING = 'running';
    const STATE_STOPPED = 'stopped';
    const STATE_FORCE_STOPPED = 'force-stopped';
    const STATE_TIMEOUT = 'timed-out';

    const SLEEP_USEC = 0.2 * 1E6;

    /**
     * @var string
     */
    private $command;

    /**
     * @var string[]
     */
    private $environment;

    /**
     * @var string
     */
    private $workingDirectory;

    /**
     * @var string[]
     */
    private $arguments;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var resource[]
     */
    private $pipes;

    /**
     * @var resource
     */
    private $process;

    /**
     * @var string
     */
    private $output = '';

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var float
     */
    private $startTime;

    /**
     * @var string
     */
    private $state;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * Process constructor.
     *
     * @param string   $command
     * @param string[] $arguments
     * @param string[] $environment
     * @param string   $workingDirectory
     * @param int      $timeout
     * @param callable $receivedContentCallback
     */
    public function __construct(
        $command,
        array $arguments = [],
        array $environment = [],
        $workingDirectory = null,
        $timeout = 60,
        callable $receivedContentCallback = null
    ) {
        if (!is_string($command)) {
            throw new \InvalidArgumentException('Argument "command" must be of type "string"');
        }
        if (!is_null($workingDirectory) && !is_string($workingDirectory)) {
            throw new \InvalidArgumentException('Argument "workingDirectory" must be NULL or of type "string"');
        }
        $this->command = $command;
        $this->environment = $environment;
        $this->workingDirectory = $workingDirectory ?: getcwd();
        $this->arguments = $arguments;
        $this->buildReceivedContentCallback($receivedContentCallback);
        $this->timeout = (int)$timeout;
        $this->state = self::STATE_READY;
    }


    public function execute(callable $receivedContentCallback = null)
    {
        $this->start($receivedContentCallback);

        $this->waitUntilEnd(false);
        $this->cleanup();

        return $this->output;
    }

    /**
     * @param callable $receivedContentCallback
     */
    public function start(callable $receivedContentCallback = null)
    {
        $this->buildReceivedContentCallback($receivedContentCallback);
        $descriptorSpec = $this->getDescriptors();

        $this->startTime = microtime(true);

        $this->process = proc_open(
            $this->buildCommand(),
            $descriptorSpec,
            $this->pipes,
            $this->workingDirectory,
            $this->environment
        );

        if (!is_resource($this->process)) {
            throw new ProcessException('Process could not be started');
        }

        $this->updateState();
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
    }

    private function getDescriptors()
    {
        return [
            0 => ['pipe', 'r'], // stdin is a pipe that the child will read from
            1 => ['pipe', 'w'], // stdout is a pipe that the child will write to
            2 => ['pipe', 'w'], // stderr is a pipe that the child will write to
        ];
    }


    private function waitUntilEnd($blocking)
    {
        // Reset the output
        $this->output = '';

        while ($this->isRunning()) {
            $readBytesCollection = $this->wait($blocking);

            $this->output .= implode('', $readBytesCollection);
            $this->updateState();
        }
    }

    /**
     * @param bool $blocking
     * @return string[]
     */
    private function wait($blocking)
    {
        $readStreams = $this->pipes;
        unset($readStreams[0]);

        $readBytesCollection = [];
        $writeStreams = [];
        $exceptStreams = [];

        $this->setPipesBlocking($blocking);

        $ready = stream_select($readStreams, $writeStreams, $exceptStreams, 0, $blocking ? self::SLEEP_USEC : 0);
        if ($ready === false) {
            if ($this->hasSystemCallBeenInterrupted()) {
                $this->printError('hasSystemCallBeenInterrupted');

                return [];
            }

            $this->printError('stream_select failed');

            return [];
        }

        if ($ready > 0) {
            foreach ($readStreams as $type => $stream) {
                $readBytesCollection[$type] = $received = stream_get_contents($stream);
                call_user_func($this->callback, $received);
            }
        }

        return $readBytesCollection;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->state === self::STATE_RUNNING;
    }

    public function isTerminated()
    {
        return !$this->isRunning();
    }

    public function getExitCode()
    {
        if ($this->isRunning()) {
            return -1;
        }
        $state = $this->getProcessStatus();

        return $state ? $state['exitcode'] : -1;
    }

    public function stop($signal = SIGTERM)
    {
        if ($this->isRunning()) {
            proc_terminate($this->process, $signal);
            $this->cleanup();
            $this->state = self::STATE_STOPPED;
        }
    }

    /**
     * Returns the state of the process
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    private function getProcessStatus()
    {
        if (!$this->process) {
            return [];
        }

        return proc_get_status($this->process);
    }

    private function cleanup()
    {
        if ($this->isRunning()) {
            throw new ProcessException('Process is still running');
        }

        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);

        proc_close($this->process);

        $this->callback = null;
    }

    /**
     * @return string
     */
    private function buildCommand()
    {
        return $this->command . ' ' . implode(' ', array_map('escapeshellarg', $this->arguments));
    }

    /**
     * @param callable $receivedContentCallback
     */
    private function buildReceivedContentCallback(callable $receivedContentCallback = null)
    {
        if (null !== $receivedContentCallback) {
            $this->callback = $receivedContentCallback;
        } else {
            $this->callback = function () {

            };
        }
    }

    private function setPipesBlocking($blocking)
    {
        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, (bool)$blocking);
        }
    }

    private function forceStop()
    {
        $this->state = self::STATE_FORCE_STOPPED;
        // Try nice stop
        try {
            $this->stop();
        } catch (ProcessException $exception) {
        }

        if ($this->isRunning()) {
            // Kill
            try {
                $this->stop(SIGKILL);
            } catch (ProcessException $exception) {
            }
        }
    }

    public function __destruct()
    {
        $this->forceStop();
    }

    private function hasSystemCallBeenInterrupted()
    {
        $lastError = error_get_last();

        // stream_select returns false when the `select` system call is interrupted by an incoming signal
        return isset($lastError['message']) && false !== stripos($lastError['message'], 'interrupted system call');
    }

    private function updateState()
    {
        if (!$this->process) {
            $this->state = self::STATE_READY;

            return;
        }

        $oldState = $this->state;
        $status = $this->getProcessStatus();

        $statusRunning = $status['running'];
        if ($oldState === self::STATE_READY && true === $statusRunning) {
            $this->state = self::STATE_RUNNING;
        }

        if ($oldState === self::STATE_RUNNING && false === $statusRunning) {
            $this->state = self::STATE_STOPPED;
        }

        if (false === $this->checkTimeout()) {
            $this->state = self::STATE_TIMEOUT;
        }

        $this->debugState();
    }

    /**
     * Check if the custom timeout has been reached
     *
     * @return bool
     */
    private function checkTimeout()
    {
        $maxEndTime = $this->startTime + $this->timeout * 1000 * 1000;

        // Not timed out
        if ($maxEndTime > microtime(true)) {
            return true;
        }

        $this->forceStop();
        $this->printError('Waiting for process response timed out');

        return false;
    }

    private function debugState()
    {
        if ($this->debug) {
            switch ($this->state) {
                case self::STATE_READY :
                    $this->debugPrint('0');
                    break;
                case self::STATE_RUNNING :
                    $this->debugPrint('R');
                    break;
                case self::STATE_STOPPED :
                    $this->debugPrint('S');
                    break;
                case self::STATE_FORCE_STOPPED :
                    $this->debugPrint('X');
                    break;
                case self::STATE_TIMEOUT :
                    $this->debugPrint('T');
                    break;
            }
        }
    }

    /**
     * @param string $message
     */
    private function printError($message)
    {
        if ($this->debug) {
            fwrite(STDERR, '[ERROR] ' . $message . PHP_EOL);
        }
    }

    /**
     * @param $message
     */
    private function debugPrint($message)
    {
        if ($this->debug) {
            fwrite(STDOUT, $message);
        }
    }
}
