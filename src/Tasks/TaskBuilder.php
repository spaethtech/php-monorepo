<?php
declare(strict_types=1);

namespace App\Tasks;

use Closure;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Style\SymfonyStyle;

class TaskBuilder
{
    /**
     * @var TaskBuilderEntry[]
     */
    protected array $tasks = [];



    protected string $shell = "bash";
    protected string $shellArgs = "-c";

    protected bool $stopOnFailure = true;

    protected bool $hideOutput = false;

    protected bool $hideErrors = false;

    //public SymfonyStyle $io;

//    public function __construct(Input $input, Output $output)
//    {
//        $this->io = new SymfonyStyle($input, $output);
//    }

    public function setShell(string $shell, string $args): self
    {
        $this->shell = $shell;
        $this->shellArgs = $args;
        return $this;
    }

    public function hideOutput(): self
    {
        $this->hideOutput = true;
        return $this;
    }

    public function showOutput(): self
    {
        $this->hideOutput = false;
        return $this;
    }

    public function hideErrors(): self
    {
        $this->hideErrors = true;
        return $this;
    }
    public function showErrors(): self
    {
        $this->hideErrors = false;
        return $this;
    }

    public function stopOnFailure(bool $stopOnFailure = true): self
    {
        $this->stopOnFailure = $stopOnFailure;
        return $this;
    }

    public function add(TaskInterface $task, Closure $conditional = null): self
    {
        $this->tasks[] = new TaskBuilderEntry($task, $conditional);
        return $this;
    }

    public function addCommand(string $command, Closure $conditional = null): self
    {
        $task = new TaskCommand($command);//, $this->io);
        $task->setShell($this->shell, $this->shellArgs);
        $task->hideOutput($this->hideOutput);
        $task->hideErrors($this->hideErrors);

        return $this->add($task, $conditional);
    }

    public function addClosure(Closure $closure): self
    {
        $task = new TaskClosure($closure);
        return $this->add($task);
    }



    public function run(): TaskResult|false
    {
        $successful = TaskResult::SUCCESS;

        for ($i = 0; $i < count($this->tasks); $i++)
        {
            $current = $this->tasks[$i];
            $previous = ($i > 0) ? $this->tasks[$i - 1] : null;

            if ($current->conditional instanceof Closure && !(($current->conditional)($current, $previous)))
                continue; //Skip

            $result = $current->task->run();

            if ($result === TaskResult::SUCCESS)
                continue;

            if ($this->stopOnFailure)
            {
                if ($current->task instanceof TaskCommand)
                {
                    $exitCode = $current->task->getProcess()->getExitCode();
                    return $current->task->printTaskFailure("Command failed with exit code: $exitCode");
                }

                return TaskResult::FAILURE;
            }

            $successful = TaskResult::FAILURE;
        }

        return $successful;
    }


}
