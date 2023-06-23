<?php
declare(strict_types=1);

namespace App\Console\Commands\MonoRepo;

use App\Tasks\Task;
use App\Tasks\PhpStorm\Vcs\VcsTask;
use App\Tasks\TaskStack;
use App\Tasks\CommandTask;
use App\Tasks\TaskInterface;
use App\Tasks\TaskResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[AsCommand(
    name: "mod:add",
    description: "Adds an existing module to this repo"
)]
class ModAddCommand extends ModuleCommand
{
    protected function execute(Input $input, Output $output): int
    {
        $result = (new TaskStack())
            // Ignore errors
            ->hideStdErr()

            // Set the execution shell to "bash" for our operations
            ->setShell("bash", "-c")

            // Check for existing submodule
            ->add(function(Task $current)
            {
                    return file_exists($this->path)
                        ? $current->printTaskFailure("Submodule $this->full already exists!")
                        : TaskResult::SUCCESS;
            })

            // Check for remote repository
            ->add(new CommandTask("git ls-remote $this->url --quiet", true))
            ->add(function(Task $current, CommandTask $previous)
            {
                return ($previous->getProcess()->getExitCode() !== 0)
                    ? TaskResult::FAILURE  //$current->printTaskFailure("Repository not found: $this->url")
                    : TaskResult::SUCCESS;
            })

            // Reset Git and add the submodule and associated files/folders
            ->add("git reset")
            ->add("git submodule add --name $this->full $this->url $this->path")
            ->add("git add .gitmodules")
            ->add("git add $this->path")

            // Check for staged changes and commit if needed
            ->add(new CommandTask("git diff --cached --quiet --exit-code", true, true, [1]))
            ->add("git commit -m 'Added submodule $this->full to repository'", //CommandTask::previousExitCodeIs1(...)
                function(TaskInterface $current, CommandTask $previous): bool
                {
                    return $previous->getProcess()->getExitCode() === 1;
                }
            )

            // Add related PhpStorm VCS configuration
            ->add((new VcsTask())->add($this->path))

            ->run();

        return $result === TaskResult::SUCCESS ? Command::SUCCESS : Command::FAILURE;
    }


}
