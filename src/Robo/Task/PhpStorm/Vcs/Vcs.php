<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Robo\Task\PhpStorm\Vcs;

use App\Robo\Task\PhpStorm\ChangeInfo;
use App\Robo\Task\PhpStorm\ChangeType;
use App\Robo\Task\PhpStorm\Component;
use App\Robo\Task\PhpStorm\XmlBaseTask;
use DOMException;
use DOMNode;
use Robo\Result;

class Vcs extends XmlBaseTask
{
    /**
     * @var array<string, ChangeInfo>
     */
    protected array $changes = [];

    public function __construct()
    {
        parent::__construct(Component::VCS);
    }

    public function add(string $path, string $vcs = "Git"): self
    {
        $path = $this->projectPath($path);
        $this->changes[$path] = new VcsChangeInfo(ChangeType::ADD, $path, $vcs);
        return $this;
    }

    public function del(string $path): self
    {
        $path = $this->projectPath($path);
        $this->changes[$path] = new VcsChangeInfo(ChangeType::DEL, $path);
        return $this;
    }



    protected function getMapping(string $path = ""): DOMNode|null
    {
        $path = $this->projectPath($path);
        return $this->xpath
            ->query("./mapping[@directory='$path']", $this->getComponent())
            ->item(0);
    }

    protected function addMapping(string $path, VcsChangeInfo $change): bool
    {
        if ($change->type !== ChangeType::ADD)
            return false;

        $path = $this->projectPath($path);
        $node = $this->getMapping($path);

        try
        {
            $mapping = $this->document->createElement("mapping");
        }
        catch(DOMException)
        {
            return false;
        }

        $mapping->setAttribute("directory", $path);
        $mapping->setAttribute("vcs", $change->vcs);

        if($node !== null)
        {
            if ($this->document->saveXML($node) === $this->document->saveXML($mapping))
            {
                return false;
            }
            else
            {
                $this->printTaskInfo("* {$this->document->saveXML($mapping)}");
                $this->getComponent()->replaceChild($mapping, $node);
                return true;
            }
        }

        $this->printTaskInfo("+ {$this->document->saveXML($mapping)}");
        $this->getComponent()->appendChild($mapping);
        return true;
    }

    protected function delMapping(string $path, VcsChangeInfo $change): bool
    {
        if ($change->type !== ChangeType::DEL)
            return false;

        $path = $this->projectPath($path);
        $node = $this->getMapping($path);

        if ($node === null)
            return false;

        $this->printTaskInfo("- {$this->document->saveXML($node)}");
        $node->parentNode->removeChild($node);
        return true;
    }



    public function run(): Result
    {
        $this->load();

        foreach($this->changes as $directory => $changeInfo)
        {
            match($changeInfo->type)
            {
                ChangeType::ADD => $this->addMapping($directory, $changeInfo),
                ChangeType::DEL => $this->delMapping($directory, $changeInfo),
                //default => die("Unknown Change Type!"),
            };

        }

        $this->save();

        return Result::success($this);
    }
}

