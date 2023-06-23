<?php
declare(strict_types=1);

namespace App\Console\Commands\MonoRepo;

use App\Tasks\Git\GitModulesRemoveTask;
use App\Tasks\PhpStorm\Vcs\VcsTask;
use App\Tasks\TaskStackEntry;
use App\Tasks\CommandTask;
use App\Tasks\TaskInterface;
use App\Tasks\TaskResult;
use App\Tasks\TaskStack;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[AsCommand(
    name: "mod:del",
    description: "Removes a module from this repo"
)]
class ModDelCommand extends ModuleCommand
{
    protected function execute(Input $input, Output $output): int
    {
        $result = (new TaskStack())
            // Force all cleaning steps!
            ->stopOnFailure(false)

            // Ignore errors
            ->hideStdErr()

            // Reset Git and remove submodule and associated files/folders
            ->add("git reset")
            ->add("git submodule deinit -f $this->path")
            ->add("git rm --cached")
            ->add("rm -rf .git/modules/$this->full")
            ->add("rm -rf $this->path")

            // Remove the associated entry from the .gitmodules file and stage for commit
            ->add(new GitModulesRemoveTask($this->full, $this->path, $this->url))
            ->add("git add .gitmodules")

            // Check the submodule folder for changes and stage if needed
            ->add(new CommandTask("git ls-files --error-unmatch $this->path", true, true))
            ->add("git add $this->path",
                function(TaskInterface $current, CommandTask $previous): bool
                {
                    return $previous->getProcess()->getExitCode() === 0;
                }
            )

            // Check for staged changes and commit if needed
            ->add(new CommandTask("git diff --cached --quiet --exit-code", true, true, [1]))
            ->add("git commit -m 'Removed submodule $this->full'",
                function(TaskInterface $current, CommandTask $previous): bool
                {
                    return $previous->getProcess()->getExitCode() === 1;
                }
            )

            // Remove related PhpStorm VCS configuration
            ->add((new VcsTask())->del($this->path))

            ->run();

        return $result === TaskResult::SUCCESS ? Command::SUCCESS : Command::FAILURE;
    }

}
