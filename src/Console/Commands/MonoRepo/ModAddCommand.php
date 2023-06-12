<?php
declare(strict_types=1);

namespace App\Console\Commands\MonoRepo;

use App\Console\Commands\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModAddCommand extends BaseCommand
{
    private const DEFAULT_URL = "https://github.com";
    private const DEFAULT_DIR = "lib";
    private const DEFAULT_ORG = "spaethtech";

    private const PATTERN_ORG = "/^[a-z0-9-]+$/";
    private const PATTERN_MOD = "/^[a-z0-9-]+$/";

    protected function configure(): void
    {
        $this->setName("mod:add")
            ->setDescription("Adds an existing module to this repo")
            ->addArgument("name", InputArgument::REQUIRED,
                "The name of the module")
            ->addOption("dir", "d", InputOption::VALUE_REQUIRED,
                "Modules directory, either absolute or relative to PROJECT_DIR",
                self::DEFAULT_DIR)
            ->addOption("org", "o", InputOption::VALUE_REQUIRED,
                "The organization (or owner) of the module",
                self::DEFAULT_ORG)
            ->addOption("url", "u", InputOption::VALUE_REQUIRED,
                "The remote Git repo to use instead of the auto-generated URL")
            ->addOption("force", "f", InputOption::VALUE_NONE,
                "Forces replacement of an existing module");
    }

    protected function execute(Input $input, Output $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Arguments
        $name = $input->getArgument("name");

        if (!preg_match(self::PATTERN_MOD, $name))
            die("Invalid repository name, must match: ".self::PATTERN_MOD);

        // Options
        $dir = $input->getOption("dir");
        $org = $input->getOption("org");
        $url = $input->getOption("url") ?? self::DEFAULT_URL."/$org/$name";

        if (!preg_match(self::PATTERN_ORG, $org))
            die("Invalid organization name, must match: ".self::PATTERN_ORG);

        $lib = "$dir/$name";
        $mod = "$org/$name";

        if (file_exists($lib))
            die("Submodule has already been added!");

        if(!$this->remoteRepoExists($url))
        {
            $io->error("Repository not found: $url");
            return Command::FAILURE;
        }

        $this->bash([
            "git reset",
            "git submodule add --name $mod $url $lib",
            "git add .gitmodules",
            "git add $lib",
            "git commit -m 'Added submodule $mod to repository'",
        ]);

        return Command::SUCCESS;
    }


    private function remoteRepoExists(string $url): bool
    {
        exec("git ls-remote $url -q 2>&1 /dev/null", $output, $result);
        return $result === 0;
    }


}
