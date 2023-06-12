<?php
declare(strict_types=1);

namespace App\Tasks;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractTask
{
    protected SymfonyStyle $io;

    protected string $cwd = PROJECT_DIR;

    protected array $env = [];

    public function setCwd(string $cwd): self
    {
        $this->cwd = $cwd;
        return $this;
    }

    public function setEnv(string $key, $value): self
    {
        $this->env[$key] = $value;
        return $this;
    }


    protected function __construct(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->io = new SymfonyStyle(
            $input ?? new ArgvInput(),
            $output ?? new ConsoleOutput()
        );

    }


}
