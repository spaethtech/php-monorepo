<?php
declare(strict_types=1);

namespace App\Console\Commands;

use http\Exception\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BaseCommand extends Command
{
    protected SymfonyStyle $io;
    protected string $shell = "bash -c";
    protected string $executable = "";

    protected function buildCommand(string $command): string
    {
        return "$this->shell \"$this->executable $command\"";
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $name = $input->getArgument("name");
        $this->io->writeln($name);
    }

//    protected function exec(array|string $command, array &$output = null): int|array
//    {
//        $results = [];
//
//        foreach(is_array($command) ? $command : [ $command ] as $cmd)
//        {
//            if($output === null)
//                passthru($this->buildCommand($cmd), $result);
//            else
//                exec($this->buildCommand($cmd), $output, $result);
//
//            $results[] = $result;
//        }
//
//        return is_array($command) ? $results : $results[0];
//
//    }

    public function askYesNo(string $question, bool $default = false, int $maxAttempts = 0): bool
    {
        $answer = $this->askRestricted($question, [ "y", "n" ], "n", $maxAttempts);
        return $answer !== false && strtolower($answer) === "y";
    }

    public function askRestricted(
        string $question,
        array $answers = [ "y", "n" ],
        string $default = "n",
        int $maxAttempts = 0
    ) : string|false
    {
        $answers = array_unique(array_map("strtolower", $answers));

        if (!in_array(strtolower($default), $answers))
            throw new InvalidArgumentException("Answers must include the specified default!");

        $answerStr = implode("/", array_map(function ($answer) use ($default) {
            return $answer === strtolower($default) ? strtoupper($answer) : strtolower($answer);
        }, $answers));

        $attempts = 0;

        while($maxAttempts === 0 || $attempts < $maxAttempts)
        {
            $answer = $this->io->ask("$question [$answerStr]", strtoupper($default));

            if (in_array(strtolower($answer), $answers))
                return $answer;

            $this->io->writeln("Expected one of: ".implode("/", $answers));
            $attempts++;

            if($maxAttempts > 0)
                $this->io->writeln("Attempts remaining: ".($maxAttempts - $attempts));
        }

        return false;
    }

}
