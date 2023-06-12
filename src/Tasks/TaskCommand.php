<?php
declare(strict_types=1);

namespace App\Tasks;

use Closure;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class TaskCommand extends AbstractTask implements TaskInterface
{
    protected string $shell = "bash";
    protected string $shellArgs = "-c";

    protected bool $hideOutput = false;
    protected bool $hideErrors = false;

    public function __construct(
        protected string $command,
        protected bool $hidden = false,
        protected array $successfulExitCodes = [ 0 ]
        //protected ?Closure $resultMapper = null
    )
    {
        parent::__construct();

        if ($this->hidden)
        {
            $this->hideOutput = true;
            $this->hideErrors = true;
        }

//        $this->resultMapper ??= function(Process $p): TaskResult
//        {
//            return $p->getExitCode() === 0
//                ? TaskResult::SUCCESS
//                : TaskResult::FAILURE;
//        };
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

        //$resultMapper = $this->resultMapper;


        //return $this->process->getExitCode() === $this->successExitCode
        return in_array($this->process->getExitCode(), $this->successfulExitCodes)
        //return $resultMapper($this->process)
            ? TaskResult::SUCCESS
            : TaskResult::FAILURE;
    }


}
