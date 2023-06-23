<?php
declare(strict_types=1);

namespace App\Tasks;

use Closure;

class TaskBuilderEntry
{
    public function __construct(
        public TaskInterface|Closure $task,
        public ?Closure $conditional = null
    )
    {
    }

}
