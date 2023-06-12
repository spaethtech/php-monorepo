<?php
declare(strict_types=1);

namespace App\Tasks;

use Closure;
use Symfony\Component\Console\Style\SymfonyStyle;

class TaskClosure extends AbstractTask implements TaskInterface
{
    public function __construct(protected Closure $closure)
    {
        parent::__construct();
    }

    public function run(): TaskResult|false
    {
        $closure = $this->closure;
        $result = $closure();

        if($result instanceof TaskResult)
            return $result;

        return false;
    }


}
