<?php
declare(strict_types=1);

namespace App\Tasks\PhpStorm;


abstract class ChangeInfo
{
    public function __construct(public ChangeType $type)
    {
    }

}
