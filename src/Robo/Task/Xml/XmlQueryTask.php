<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Robo\Task\Xml;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use JetBrains\PhpStorm\Language;
use Robo\Result;
use Robo\Task\BaseTask;

class XmlQueryTask extends BaseTask
{
    protected DOMDocument $document;

    protected DOMXPath $xpath;

    /**
     * @lang XPath
     * @var string The query expression
     */
    protected string $expression;

    protected ?DOMNode $context = null;

    protected bool $registerNamespace = true;

    protected ?Closure $errorFunc;

    protected ?Closure $eachFunc;
    protected ?Closure $firstFunc;

    public function __construct(DOMDocument $document)
    {
        $this->document = $document;
        $this->xpath = new DOMXPath($this->document);
    }

    public function expression(#[Language("XPath")] string $expression): self
    {
        $this->expression = $expression;
        return $this;
    }

    public function context(DOMNode $node): self
    {
        $this->context = $node;
        return $this;
    }


    public function registerNamespace(bool $register): self
    {
        $this->registerNamespace = $register;
        return $this;
    }

    public function error(Closure $func): self
    {
        $this->errorFunc = $func;
        return $this;
    }

    public function each(callable $func): self
    {
        $this->eachFunc = $func(...);
        return $this;
    }


    public function run(): Result
    {
        $nodes = $this->xpath->query(
            $this->expression,
            $this->context,
            $this->registerNamespace
        );

        if ($nodes === false)
            return $this->errorFunc?->call($this);

        for($i = 0; $i < $nodes->count(); $i++)
            $this->eachFunc?->call($this, $nodes[$i], $i);

        return Result::success($this);
    }




}
