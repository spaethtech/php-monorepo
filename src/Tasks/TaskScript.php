<?php
declare(strict_types=1);

namespace App\Tasks;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpProcess;

class TaskScript extends AbstractTask implements TaskInterface
{
    public function __construct(protected string $script)
    {
        parent::__construct();
    }

    public function run(): TaskResult|false
    {
        $process = new PhpProcess(
            $this->script,
            $this->cwd,
            $this->env
        );

        $process->run();

        return $process->getExitCode() === 0
            ? TaskResult::SUCCESS
            : TaskResult::FAILURE;
    }

}
