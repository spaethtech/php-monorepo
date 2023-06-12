<?php
declare(strict_types=1);

namespace App\Console\Commands\MonoRepo;

use App\Tasks\Git\GitModulesRemoveTask;
use App\Tasks\TaskBuilderEntry;
use App\Tasks\TaskCommand;
use App\Tasks\TaskResult;
use App\Tasks\TaskBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[AsCommand("mod:del", "Removes a module from this repo")]
class ModDelCommand extends ModuleCommand
{
    protected function execute(Input $input, Output $output): int
    {
        $lib = $this->path;
        $mod = $this->full;
        $url = $this->url;

        $result = (new TaskBuilder())
            ->stopOnFailure(false) // Force all cleaning steps!
            ->hideErrors()
            ->addCommand("git reset")
            ->addCommand("git submodule deinit -f $lib")
            ->addCommand("git rm --cached")
            ->addCommand("rm -rf .git/modules/$mod")
            ->addCommand("rm -rf $lib")

            // Remove the associated entry from the .gitmodules file and stage for commit
            ->add(new GitModulesRemoveTask($mod, $lib, $url))
            ->addCommand("git add .gitmodules")

            // Check the submodule folder for changes
            ->add(new TaskCommand("git ls-files --error-unmatch $lib", true))
            // Stage the changes, if there are any
            ->addCommand("git add $lib",
                function(TaskBuilderEntry $current, TaskBuilderEntry $previous): bool
                {
                    return $previous->task instanceof TaskCommand
                        && $previous->task->getProcess()->getExitCode() === 0;
                }
            )

            // Check for staged changes
            ->add(new TaskCommand("git diff --cached --quiet --exit-code", true, [1]))
            // Commit the staged changes
            ->addCommand("git commit -m 'Removed submodule $mod'",
                function(TaskBuilderEntry $current, TaskBuilderEntry $previous): bool
                {
                    return $previous->task instanceof TaskCommand
                        && $previous->task->getProcess()->getExitCode() === 1;
                        //&& $previous->task->getProcess()->isSuccessful();
                }
            )

            ->run();

        return $result === TaskResult::SUCCESS ? Command::SUCCESS : Command::FAILURE;
    }



}
