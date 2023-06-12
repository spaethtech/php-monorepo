<?php
declare(strict_types=1);

namespace App\Console\Commands\MonoRepo;

use App\Console\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleCommand extends BaseCommand
{
    private const DEFAULT_URL = "https://github.com";
    private const DEFAULT_DIR = "lib";
    private const DEFAULT_ORG = "spaethtech";

    private const PATTERN_ORG = "/^[a-z0-9-]+$/";
    private const PATTERN_MOD = "/^[a-z0-9-]+$/";

    protected string $name;
    protected string $dir;
    protected string $org;
    protected string $url;

    protected string $path;
    protected string $full;


    protected function configure(): void
    {
        $this
            ->addArgument("name", InputArgument::REQUIRED,
                "The name of the module")
            ->addOption("dir", "d", InputOption::VALUE_REQUIRED,
                "Modules directory, either absolute or relative to PROJECT_DIR",
                self::DEFAULT_DIR)
            ->addOption("org", "o", InputOption::VALUE_REQUIRED,
                "The organization (or owner) of the module",
                self::DEFAULT_ORG)
            ->addOption("url", "u", InputOption::VALUE_REQUIRED,
                "The remote Git repo to use instead of the auto-generated URL");
            //->addOption("force", "f", InputOption::VALUE_NONE,
            //    "Forces replacement of an existing module");
    }


    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->name = $input->getArgument("name");

        if (!preg_match(self::PATTERN_MOD, $this->name))
            die("Invalid repository name, must match: ".self::PATTERN_MOD);

        // Options
        $this->dir = $input->getOption("dir");
        $this->org = $input->getOption("org");
        $this->url = $input->getOption("url")
            ?? self::DEFAULT_URL."/$this->org/$this->name";

        if (!preg_match(self::PATTERN_ORG, $this->org))
            die("Invalid organization name, must match: ".self::PATTERN_ORG);

        $this->path = "$this->dir/$this->name";
        $this->full = "$this->org/$this->name";



    }

}
