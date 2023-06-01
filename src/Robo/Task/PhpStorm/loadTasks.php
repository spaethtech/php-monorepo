<?php
/** @noinspection PhpRedundantVariableDocTypeInspection */
declare(strict_types=1);

namespace App\Robo\Task\PhpStorm;

use App\Robo\Task\PhpStorm\Vcs\Vcs;
use Robo\Collection\CollectionBuilder;
use Robo\Tasks;

trait loadTasks
{
    protected function taskVcs(): Vcs|CollectionBuilder
    {
        /** @var Tasks $this */
        return $this->task(Vcs::class);
    }

}
