<?php
declare(strict_types=1);

namespace App\Tasks;

use Symfony\Component\Process\Process;

enum TaskResult
{
    case SUCCESS;

    case WARNING;

    case FAILURE;

}
