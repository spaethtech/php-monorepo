<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Robo\Task\PhpStorm;

use App\Robo\Task\PhpStorm\Exceptions\ComponentNotFoundException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Robo\Result;
use Robo\Task\BaseTask;

abstract class XmlBaseTask extends BaseTask
{
    protected const DIRECTORY_PREFIX = "\$PROJECT_DIR\$";

    protected Component $component;
    protected DOMDocument $document;

    protected DOMXPath $xpath;



    public function __construct(Component $component)
    {
        $this->component = $component;
    }

    protected function load(): void
    {
        $this->document = new DOMDocument();
        $this->document->formatOutput = true;
        $this->document->preserveWhiteSpace = false;
        $this->document->load($this->component->file());

        $this->xpath = new DOMXPath($this->document);
    }

    protected function save(): void
    {
        $this->document->save($this->component->file());
    }

    protected function projectPath(string $path): string
    {
        $path = trim($path);
        return str_starts_with($path, self::DIRECTORY_PREFIX)
            ? $path
            : self::DIRECTORY_PREFIX.(empty($path) ? "" : "/$path");
    }


    protected function getProject(): DOMNode|null
    {
        return $this->xpath->query("//project")->item(0);
    }

    protected function getComponent(string $name = ""): DOMNode|null
    {
        if ($name === "" || $name === null)
            $name = $this->component->name();

        return $this->xpath
            ->query("./component[@name='$name']", $this->getProject())
            ->item(0);
    }




}
