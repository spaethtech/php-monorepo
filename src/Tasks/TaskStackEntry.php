<?php
declare(strict_types=1);

namespace App\Tasks;

use Closure;

/**
 * An entry item for use in the TaskStack.
 *
 * @author Ryan Spaeth
 * @copyright Spaeth Technologies Inc.
 */
class TaskStackEntry
{
    public function __construct(
        public TaskInterface|Closure $task,
        public ?Closure $conditional = null
    )
    {
    }

}
