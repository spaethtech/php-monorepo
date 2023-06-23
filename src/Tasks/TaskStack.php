<?php
declare(strict_types=1);

namespace App\Tasks;

use Closure;

/**
 * A simple Task execution stack.
 *
 * NOTES:
 * - Tasks are executed in the order they are added.
 * - Tasks can include an optional Closure (or bool value) to allow for conditional execution.
 * - Tasks can be added as a TaskInterface, Closure or a string which is converted to a CommandTask.
 *
 * @author Ryan Spaeth
 * @copyright Spaeth Technologies Inc.
 */
class TaskStack implements TaskInterface
{
    /** @var TaskStackEntry[] */
    protected array $entries = [];

    protected string $shell = "bash";
    protected string $shellArgs = "-c";

    protected bool $stopOnFailure = true;

    protected bool $hideStdOut = false;

    protected bool $hideStdErr = false;

    #region Getters & Setters

    /**
     * Sets the shell to use when adding commands dynamically
     *
     * NOTES:
     * - This only affects commands added using TaskBuilder::addCommand()
     *
     * @param string $shell The shell to use (i.e. "bash", "cmd.exe", etc.)
     * @param string $args  Any shell arguments needed for execution (i.e. "-c", "/c", etc.)
     *
     * @return $this for method chaining
     */
    public function setShell(string $shell, string $args): self
    {
        $this->shell = $shell;
        $this->shellArgs = $args;
        return $this;
    }

    /**
     * Sets the visibility of STDOUT to use when adding commands dynamically
     *
     * @param bool $hide When TRUE, will hide STDOUT
     *
     * @return $this for method chaining
     */
    public function hideStdOut(bool $hide = true): self
    {
        $this->hideStdOut = $hide;
        return $this;
    }

    /**
     * Sets the visibility of STDERR to use when adding commands dynamically
     *
     * @param bool $hide When TRUE, will hide STDERR
     *
     * @return $this for method chaining
     */
    public function hideStdErr(bool $hide = true): self
    {
        $this->hideStdErr = $hide;
        return $this;
    }



    public function stopOnFailure(bool $stopOnFailure = true): self
    {
        $this->stopOnFailure = $stopOnFailure;
        return $this;
    }

    #endregion

    public function add(TaskInterface|Closure|string $task, Closure|bool $conditional = true): self
    {
        if (is_string($task))
        {
            $task = new CommandTask($task);
            $task->setShell($this->shell, $this->shellArgs);
            $task->hideStdOut($this->hideStdOut);
            $task->hideStdErr($this->hideStdErr);
        }

        if(is_bool($conditional))
            $conditional = fn(): bool => $conditional;

        $this->entries[] = new TaskStackEntry($task, $conditional);
        return $this;
    }

    public function run(): TaskResult|false
    {
        $successful = TaskResult::SUCCESS;

        for ($i = 0; $i < count($this->entries); $i++)
        {
            $currCond = $this->entries[$i]->conditional;
            $currTask = $this->entries[$i]->task;
            $prevTask = ($i > 0) ? $this->entries[$i - 1]->task : null;

            if ($currCond instanceof Closure && !($currCond($currTask, $prevTask)))
                continue; //Skip

            $result = $currTask instanceof Closure
                ? $currTask(new Task(), $prevTask)
                : $currTask->run();

            if(!$result instanceof TaskResult)
                $result = false;

            if ($result === TaskResult::SUCCESS)
                continue;

            if ($this->stopOnFailure)
            {
                if ($currTask instanceof CommandTask)
                {
                    $exitCode = $currTask->getProcess()->getExitCode();
                    return $currTask->printTaskFailure("Command failed with exit code: $exitCode");
                }

                return TaskResult::FAILURE;
            }

            $successful = TaskResult::FAILURE;
        }

        return $successful;
    }


}
