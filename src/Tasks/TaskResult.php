<?php
declare(strict_types=1);

namespace App\Tasks;

/**
 * The possible results of an executed Task.
 *
 * @author Ryan Spaeth
 * @copyright Spaeth Technologies Inc.
 */
enum TaskResult
{
    case SUCCESS;

    case WARNING;

    case FAILURE;

}
