<?php
declare(strict_types=1);

namespace App\Tasks\Git;

use App\Tasks\Task;
use App\Tasks\TaskInterface;
use App\Tasks\TaskResult;

class GitModulesRemoveTask extends Task implements TaskInterface
{
    /**
     * @param string $name  The name of the submodule to match
     * @param string $path  The path of the submodule to match, defaults to .*
     * @param string $url   The URL of the submodule to match, defaults to .*
     * @param string $file  The .gitmodules file, defaults to the PROJECT_DIR
     */
    public function __construct(
        protected string $name,         // We do NOT want name defaulting to a
                                        // wildcard match like the others, as it
                                        // could potentially clear .gitmodules!
        protected string $path = ".*",  // Matches ANY path
        protected string $url  = ".*",  // Matches ANY URL
        protected string $file = PROJECT_DIR."/.gitmodules"
    )
    {
        parent::__construct();
    }

    public function run() : TaskResult|false
    {
        $this->io->writeln("");
        $this->printTaskMessage("Removing $this->name from .gitmodules");

        if (!is_file($this->file))
            return $this->printTaskWarning(
                "The .gitmodules file could not be found, skipping!");

        if (($original = file_get_contents($this->file)) === false)
            return $this->printTaskFailure(
                "An error occurred while reading from the .gitmodules file!");

        $pattern = /** @lang RegExp */
            "|^\[submodule \"(?<name>$this->name)\"]$".
            "\s*path = (?<path>$this->path)$".
            "\s*url = (?<url>$this->url)$\s*|m";

        $modified = preg_replace($pattern, "", $original);

        if($modified === $original)
            return $this->printTaskSuccess(
                "The .gitmodules file is already up-to-date!");

        if (file_put_contents($this->file, $modified) === FALSE)
            return $this->printTaskFailure(
                "An error occurred while writing to the .gitmodules file!");

        return $this->printTaskSuccess(
            "The .gitmodules file has been updated successfully!");
    }
}
