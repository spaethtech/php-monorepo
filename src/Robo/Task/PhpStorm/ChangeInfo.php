<?php
declare(strict_types=1);

namespace App\Robo\Task\PhpStorm;

use DOMElement;

abstract class ChangeInfo
{
    //public DOMElement $element;

    public ChangeType $type;

    public function __construct(/*DOMElement $element,*/ ChangeType $type)
    {
        //$this->element = $element;
        $this->type = $type;
    }

}
