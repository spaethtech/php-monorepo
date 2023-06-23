<?php
declare(strict_types=1);

namespace App\Tasks;

use Symfony\Component\Process\Process;

class CommandTask extends Task implements TaskInterface
{
    protected string $shell = "bash";
    protected string $shellArgs = "-c";

    /**
     * @param string $command
     * @param bool $hideStdOut
     * @param bool $hideStdErr
     * @param int[] $successfulExitCodes
     */
    public function __construct(
        protected string $command,
        protected bool $hideStdOut = false,
        protected bool $hideStdErr = false,
        protected array $successfulExitCodes = [ 0 ]
    )
    {
        parent::__construct();
    }

    protected function buildCommand(): string
    {
        return "$this->shell $this->shellArgs \"$this->command\"";
    }

    public function setShell(string $shell, string $args): self
    {
        $this->shell = $shell;
        $this->shellArgs = $args;
        return $this;
    }

    public function hideStdOut(bool $hideStdOut = true): self
    {
        $this->hideStdOut = $hideStdOut;
        return $this;
    }

    public function hideStdErr(bool $hideStdErr = true): self
    {
        $this->hideStdErr = $hideStdErr;
        return $this;
    }

    protected Process $process;

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function printTaskMessage(string $message = "", string $format = "bg=cyan"): void
    {
        $this->io->writeln("<$format>[ {$this->getTaskName()} ($this->shell) ]</> $message");
    }

    public function run(): TaskResult|false
    {
        $cmd = $this->buildCommand();

        $this->process = Process::fromShellCommandline(
            $cmd,
            $this->cwd,
            $this->env
        );

        $this->io->writeln("");
        $this->printTaskMessage("$this->command");

        $this->process->run(
            function($type, $buffer)
            {
                switch($type)
                {
                    case Process::OUT:
                        if(!$this->hideStdOut)
                            $this->io->write($buffer);
                        break;
                    case Process::ERR:
                        if(!$this->hideStdErr)
                            $this->io->error($buffer);
                        break;
                    default:
                        break;
                }
            }
        );

        return in_array($this->process->getExitCode(), $this->successfulExitCodes)
            ? TaskResult::SUCCESS
            : TaskResult::FAILURE;
    }


}
