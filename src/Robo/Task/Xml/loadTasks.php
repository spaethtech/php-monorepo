<?php
/** @noinspection PhpRedundantVariableDocTypeInspection */
declare(strict_types=1);

namespace App\Robo\Task\Xml;

use DOMDocument;
use Robo\Collection\CollectionBuilder;
use Robo\Tasks;

trait loadTasks
{
    protected function taskXmlQuery(DOMDocument $document): XmlQueryTask|CollectionBuilder
    {
        /** @var Tasks $this */
        return $this->task(XmlQueryTask::class, $document);
    }

}
