<?php
declare(strict_types=1);

namespace App\Tasks\FileSystem;

use App\Tasks\AbstractTask;
use App\Tasks\TaskInterface;
use App\Tasks\TaskResult;
use Closure;
use Symfony\Component\Console\Style\SymfonyStyle;

class FileRegExReplaceTask extends AbstractTask implements TaskInterface
{

    public function __construct(
        protected string $file,
        protected string $pattern,
        protected string $replace
    )
    {
        parent::__construct();
    }

    public function run(): TaskResult|false
    {
        if (!is_file($this->file))
        {
            //$this->io->writeln("TEST");
            return TaskResult::FAILURE;
        }

        if (($original = file_get_contents($this->file)) === false)
            return TaskResult::FAILURE;

        $modified = preg_replace($this->pattern, $this->replace, $original);

        return file_put_contents($this->file, $modified) !== FALSE
            ? TaskResult::SUCCESS
            : TaskResult::FAILURE;

    }


}
