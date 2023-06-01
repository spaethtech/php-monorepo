<?php
declare(strict_types=1);

namespace App\Robo\Task\PhpStorm;

enum ChangeType: string
{
    case ADD = "+";

    case DEL = "-";

    //case MOD = "*";

}
