<?php
declare(strict_types=1);

namespace App\Tasks;

/**
 * Interface for minimal Task implementation.
 *
 * @author Ryan Spaeth
 * @copyright Spaeth Technologies Inc.
 */
interface TaskInterface
{
    /**
     * Executes the given Task.
     *
     * @return TaskResult|false Returns a TaskResult or false when an implementation error occurs.
     */
    public function run(): TaskResult|false;

}
