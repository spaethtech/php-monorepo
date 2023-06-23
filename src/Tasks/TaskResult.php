<?php
declare(strict_types=1);

namespace App\Tasks;

enum TaskResult
{
    case SUCCESS;

    case WARNING;

    case FAILURE;

}
