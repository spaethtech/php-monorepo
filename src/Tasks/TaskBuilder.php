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
     * @var TaskInterface[]
     */
    protected array $tasks = [];

    protected string $shell = "bash -c";

    protected bool $stopOnFailure = true;

    protected bool $hideOutput = false;

    protected bool $hideErrors = false;

    //public SymfonyStyle $io;

//    public function __construct(Input $input, Output $output)
//    {
//        $this->io = new SymfonyStyle($input, $output);
//    }

    public function setShell(string $shell): self
    {
        $this->shell = $shell;
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

    public function add(TaskInterface $task): self
    {
        $this->tasks[] = $task;
        return $this;
    }

    public function addCommand(string $command): self
    {
        $task = new TaskCommand($command);//, $this->io);
        $task->setShell($this->shell);
        $task->hideOutput($this->hideOutput);
        $task->hideErrors($this->hideErrors);

        return $this->add($task);
    }

    public function addClosure(Closure $closure): self
    {
        $task = new TaskClosure($closure);
        return $this->add($task);
    }



    public function execute(): array
    {
        $results = [];

        foreach($this->tasks as $command)
        {
            $results[] = $result = $command->run();

            if($result !== TaskResult::SUCCESS && $this->stopOnFailure)
                break;
        }

        return $results;
    }

    public function run(): TaskResult|false
    {
        $successful = TaskResult::SUCCESS;

        foreach($this->tasks as $command)
        {
            $result = $command->run();

            if ($result === TaskResult::SUCCESS)
                continue;

            if ($this->stopOnFailure)
                return TaskResult::FAILURE;
            else
                $successful = TaskResult::FAILURE;
        }

        return $successful;
    }


}
