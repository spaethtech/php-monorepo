<?php
declare(strict_types=1);

namespace App\Tasks\PhpStorm\Vcs;

use App\Tasks\PhpStorm\ChangeInfo;
use App\Tasks\PhpStorm\ChangeType;
use DOMElement;

class VcsChangeInfo extends ChangeInfo
{
    //public string $directory;
    //public string $vcs;

    public function __construct(ChangeType $type, public string $directory, public string $vcs = "Git")
    {
        parent::__construct($type);

        //$this->directory = $directory;
        //$this->vcs = $vcs;
    }

}
