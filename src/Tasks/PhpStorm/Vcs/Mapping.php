<?php
declare(strict_types=1);

namespace App\Tasks\PhpStorm\Vcs;

use DOMDocument;
use DOMElement;

class Mapping
{
    //public DOMElement $element;
    public string $directory;
    public string $vcs;

    public function __construct(public DOMElement $element)
    {
        //$this->element = $element;
        $this->directory = $element->getAttribute("directory");
        $this->vcs = $element->getAttribute("vcs");
    }



}
