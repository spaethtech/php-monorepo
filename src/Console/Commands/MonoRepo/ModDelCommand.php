<?php
declare(strict_types=1);

namespace App\Console\Commands\MonoRepo;

use App\Tasks\FileSystem\FileRegExRemoveTask;
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
            ->stopOnFailure(false)
            ->add(new TaskCommand("git reset"))
            ->hideErrors()
            ->addCommand("git submodule deinit -f $lib")
            ->addCommand("git rm --cached")
            ->addCommand("rm -rf .git/modules/$mod")
            ->addCommand("rm -rf $lib")
            ->add(new FileRegExRemoveTask(PROJECT_DIR."/.gitmodules",
                /** @lang RegExp */
                "|^\[submodule \"(?<name>$mod)\"]$".
                "\s*path = (?<path>$lib)$".
                "\s*url = (?<url>$url)$\s*|m")
            )
            ->addCommand("git add .gitmodules")
            ->add($tracked = new TaskCommand("git ls-files --error-unmatch $lib", true))
            ->addClosure(function() use ($tracked, $lib)
            {
                return $tracked->getProcess()->getExitCode() === 0
                    ? (new TaskCommand("git add $lib"))->run()
                    : false;
            })
            ->add($staged = new TaskCommand("git diff --cached --quiet --exit-code", true))
            ->addClosure(function() use ($staged, $mod)
            {
                return $staged->getProcess()->getExitCode() === 1
                    ? (new TaskCommand("git commit -m 'Removed submodule $mod'"))->run()
                    : false;
            })
            ->run();

        return $result === TaskResult::SUCCESS ? Command::SUCCESS : Command::FAILURE;
    }



}
