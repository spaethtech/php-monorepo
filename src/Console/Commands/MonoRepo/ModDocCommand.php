<?php
declare(strict_types=1);

namespace App\Console\Commands\MonoRepo;

use App\Tasks\PhpStorm\Vcs\VcsTask;
use App\Tasks\TaskBuilder;
use App\Tasks\TaskBuilderEntry;
use App\Tasks\ClosureTask;
use App\Tasks\CommandTask;
use App\Tasks\TaskResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[AsCommand(
    name: "mod:doc",
    description: "Generates documentation for a module in this repo"
)]
class ModDocCommand extends ModuleCommand
{
    protected function execute(Input $input, Output $output): int
    {
        if (!file_exists($this->path))
            die("Submodule not found at: $this->path");

        $projectDir = realpath(PROJECT_DIR);

        if(!file_exists("$projectDir/$this->dir/php-phpdoc"))
            die("Submodule spaethtech/php-phpdoc was not found, ".
                "but can be added using the command:\n".
                "\n".
                "    repo mod:add php-phpdoc".
                "\n"
            );

        $test = "";

        $result = (new TaskBuilder())
            // Ignore errors
            ->hideStdErr()

            // Set the execution shell to "cmd" for this operation
            //->setShell("cmd", "/c")

            ->addClosure(function() use ($projectDir, &$test)
            {
                $test = exec("cygpath $projectDir");
            })

//            ->addCommand(
//                "docker run --rm ".
//                "-v \"$projectDir\":/data ".
//                "phpdoc/phpdoc ".
//                "--directory /data/$this->path/src ".
//                "--target /data/$this->path ".
//                "--cache-folder /data/.cache/$this->path/.phpdoc/ ".
//                "--title $this->full ".
//                "--template=/data/lib/php-phpdoc/templates/markdown ".
//                "2>&1" // To show progress, which is written to STDERR
//            )


            ->run();

        print_r($test);

//        $this->_exec(
//            "docker run --rm ".
//            "-v $full:/data ".
//            "phpdoc/phpdoc ".
//            "--directory /data/$lib/src ".
//            "--target /data/$lib ".
//            "--cache-folder /data/.cache/$mod/.phpdoc/ ".
//            "--title $mod ".
//            "--template=/data/lib/php-phpdoc/templates/markdown"
//        );


        return $result === TaskResult::SUCCESS ? Command::SUCCESS : Command::FAILURE;
    }


}
