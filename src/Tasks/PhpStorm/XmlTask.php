<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Tasks\PhpStorm;

use App\Tasks\Task;
use App\Tasks\TaskInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;

abstract class XmlTask extends Task implements TaskInterface
{
    protected const DIRECTORY_PREFIX = "\$PROJECT_DIR\$";

    //protected Component $component;
    protected DOMDocument $document;

    protected DOMXPath $xpath;

    public function __construct(protected Component $component)
    {
        parent::__construct();
        //$this->component = $component;
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
