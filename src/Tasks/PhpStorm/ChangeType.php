<?php
declare(strict_types=1);

namespace App\Tasks\PhpStorm;

enum ChangeType: string
{
    case ADD = "+";

    case DEL = "-";

    //case MOD = "*";

}
