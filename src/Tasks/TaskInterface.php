<?php
declare(strict_types=1);

namespace App\Tasks;

interface TaskInterface
{
    public function run(): TaskResult|false;
}
