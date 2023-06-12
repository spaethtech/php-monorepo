<?php
declare(strict_types=1);

namespace App\Tasks;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class TaskCommand extends AbstractTask implements TaskInterface
{
    protected string $shell = "bash -c";

    protected bool $hideOutput = false;
    protected bool $hideErrors = false;

    public function __construct(protected string $command, protected bool $hidden = false)
    {
        parent::__construct();

        if ($this->hidden)
        {
            $this->hideOutput = true;
            $this->hideErrors = true;
        }
    }

    protected function buildCommand(): string
    {
        return "$this->shell \"$this->command\"";
    }

    public function setShell(string $shell): self
    {
        $this->shell = $shell;
        return $this;
    }

    public function hideOutput(bool $hideOutput = true): self
    {
        $this->hideOutput = $hideOutput;
        return $this;
    }

    public function hideErrors(bool $hideErrors = true): self
    {
        $this->hideErrors = $hideErrors;
        return $this;
    }

    protected Process $process;

    public function getProcess(): Process
    {
        return $this->process;
    }


    public function run(): TaskResult|false
    {
        $cmd = $this->buildCommand();

        $this->process = Process::fromShellCommandline(
            $cmd,
            $this->cwd,
            $this->env
        );

        $this->io->writeln("<bg=blue>$cmd</>");

        $this->process->run(
            function($type, $buffer)
            {
                switch($type)
                {
                    case Process::OUT:
                        if(!$this->hideOutput)
                            $this->io->write($buffer);
                        break;
                    case Process::ERR:
                        if(!$this->hideErrors)
                            $this->io->error($buffer);
                        break;
                    default:
                        break;
                }
            }
        );

        //$this->exitCode = $process->getExitCode();

        return $this->process->getExitCode() === 0
            ? TaskResult::SUCCESS
            : TaskResult::FAILURE;
    }


}
