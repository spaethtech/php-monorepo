<?php
declare(strict_types=1);

namespace App\Tasks;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The base Task class with no implemented run() logic.
 *
 * NOTES:
 * - All other Tasks should extend from this class.
 * - Our Closure-based Task uses this class directly!
 *
 * @author Ryan Spaeth
 * @copyright Spaeth Technologies Inc.
 */
class Task
{
    protected SymfonyStyle $io;

    protected string $cwd = PROJECT_DIR;

    protected array $env = [];

    public function setCwd(string $cwd): self
    {
        $this->cwd = $cwd;
        return $this;
    }

    public function setEnv(string $key, $value): self
    {
        $this->env[$key] = $value;
        return $this;
    }


    public function __construct(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->io = new SymfonyStyle(
            $input ?? new ArgvInput(),
            $output ?? new ConsoleOutput()
        );

    }

    public function getTaskName(): string
    {
        return basename(get_called_class());
    }


    public function printTaskMessage(string $message = "", string $format = "bg=cyan"): void
    {
        $this->io->writeln("<$format>[ {$this->getTaskName()} ]</> $message");
    }

    public function printTaskSuccess(string $message = ""): TaskResult
    {
        $this->printTaskMessage($message, "bg=green");
        return TaskResult::SUCCESS;
    }

    public function printTaskWarning(string $message = ""): TaskResult
    {
        $this->printTaskMessage($message, "bg=yellow");
        return TaskResult::WARNING;
    }

    public function printTaskFailure(string $message = ""): TaskResult
    {
        $this->printTaskMessage($message, "bg=red");
        return TaskResult::FAILURE;
    }




}
