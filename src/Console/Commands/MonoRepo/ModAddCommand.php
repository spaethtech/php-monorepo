<?php
declare(strict_types=1);

namespace App\Console\Commands\MonoRepo;

use App\Console\Commands\BaseCommand;
use App\Tasks\Git\GitModulesRemoveTask;
use App\Tasks\TaskBuilder;
use App\Tasks\TaskBuilderEntry;
use App\Tasks\TaskCommand;
use App\Tasks\TaskResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand("mod:add", "Adds an existing module to this repo")]
class ModAddCommand extends ModuleCommand
{
    private const DEFAULT_URL = "https://github.com";
    private const DEFAULT_DIR = "lib";
    private const DEFAULT_ORG = "spaethtech";

    private const PATTERN_ORG = "/^[a-z0-9-]+$/";
    private const PATTERN_MOD = "/^[a-z0-9-]+$/";

    protected function configure(): void
    {
//        $this->setName("mod:add")
//            ->setDescription("Adds an existing module to this repo")
//            ->addArgument("name", InputArgument::REQUIRED,
//                "The name of the module")
//            ->addOption("dir", "d", InputOption::VALUE_REQUIRED,
//                "Modules directory, either absolute or relative to PROJECT_DIR",
//                self::DEFAULT_DIR)
//            ->addOption("org", "o", InputOption::VALUE_REQUIRED,
//                "The organization (or owner) of the module",
//                self::DEFAULT_ORG)
//            ->addOption("url", "u", InputOption::VALUE_REQUIRED,
//                "The remote Git repo to use instead of the auto-generated URL")
        parent::configure();
        $this
            ->addOption("force", "f", InputOption::VALUE_NONE,
                "Forces replacement of an existing module");
    }

    protected function execute(Input $input, Output $output): int
    {
        $lib = $this->path;
        $mod = $this->full;
        $url = $this->url;


        if (file_exists($lib))
        {
            if($input->getOption("force") || $this->io->ask("Replace existing submodule?", "N"))
            {

            }

            die("Submodule has already been added!");
        }


        $result = (new TaskBuilder())
            //->stopOnFailure(true)
            ->hideErrors()
            ->add($remote = new TaskCommand("git ls-remote $url --quiet", true))
            ->addClosure(function() use ($remote, $url)
            {
                if ($remote->getProcess()->getExitCode() !== 0)
                    return $remote->printTaskFailure(
                        "Repository not found: $url");

                return TaskResult::SUCCESS;
            })

            ->addCommand("git reset")
            ->addCommand("git submodule add --name $mod $url $lib")
            ->addCommand("git add .gitmodules")
            ->addCommand("git add $lib")

            // Check for staged changes
            ->add(new TaskCommand("git diff --cached --quiet --exit-code", true, [1]))
            // Commit the staged changes
            ->addCommand("git commit -m 'Added submodule $mod to repository'",
                function(TaskBuilderEntry $current, TaskBuilderEntry $previous): bool
                {
                    return $previous->task instanceof TaskCommand
                        && $previous->task->getProcess()->getExitCode() === 1;
                }
            )

            ->run();

        return $result === TaskResult::SUCCESS ? Command::SUCCESS : Command::FAILURE;
    }


    private function remoteRepoExists(string $url): bool
    {
        exec("git ls-remote $url -q 2>&1 /dev/null", $output, $result);
        return $result === 0;
    }


}
