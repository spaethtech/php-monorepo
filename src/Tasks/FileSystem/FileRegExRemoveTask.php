<?php
declare(strict_types=1);

namespace App\Tasks\FileSystem;

use App\Tasks\Task;
use App\Tasks\TaskInterface;
use App\Tasks\TaskResult;
use Closure;
use Symfony\Component\Console\Style\SymfonyStyle;

class FileRegExRemoveTask extends FileRegExReplaceTask
{
    public function __construct(
        protected string $file,
        protected string $pattern
    )
    {
        parent::__construct($file, $pattern, "");
    }

}
