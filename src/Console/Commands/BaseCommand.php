<?php
declare(strict_types=1);

namespace App\Console\Commands;

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

    protected function interact(InputInterface $input, OutputInterface $output)
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
}
