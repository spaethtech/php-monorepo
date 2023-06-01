<?php
declare(strict_types=1);

namespace App\Robo\Task\PhpStorm\Vcs;

use App\Robo\Task\PhpStorm\ChangeInfo;
use App\Robo\Task\PhpStorm\ChangeType;
use DOMElement;

class VcsChangeInfo extends ChangeInfo
{
    public string $directory;
    public string $vcs;

    public function __construct(/*DOMElement $element,*/ ChangeType $type, string $directory, string $vcs = "Git")
    {
        parent::__construct(/*$element,*/ $type);

        $this->directory = $directory;
        $this->vcs = $vcs;
    }

}
