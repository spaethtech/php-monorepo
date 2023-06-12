<?php
declare(strict_types=1);

namespace App\Tasks;

use Closure;

class TaskBuilderEntry
{
    public function __construct(
        public TaskInterface $task,
        //public Closure|bool $conditional = true
        public ?Closure $conditional = null
    )
    {
    }

}
