<?php
declare(strict_types=1);

namespace App\Tasks\Git;

use App\Tasks\AbstractTask;
use App\Tasks\FileSystem\FileRegExReplaceTask;
use App\Tasks\TaskInterface;
use App\Tasks\TaskResult;
use Closure;
use Symfony\Component\Console\Style\SymfonyStyle;

class GitModulesRemoveTask extends AbstractTask implements TaskInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function run() : TaskResult|false
    {
        return false;
    }
}
