<?php
/**
 * @noinspection PhpUnused
 * @noinspection PhpUnusedParameterInspection
 * @noinspection PhpUnusedPrivateMethodInspection
 */
declare(strict_types=1);

use App\Exceptions\CommandExecutionException;
use Robo\Symfony\ConsoleIO;
use Robo\Tasks;
use SpaethTech\Robo\Task\MonoRepo;
use SpaethTech\Support\FileSystem;
use SpaethTech\Support\Process;
use SpaethTech\Support\Version;
use SpaethTech\WSL\Exceptions\ContainerCreationException;
use SpaethTech\WSL\Exceptions\ContainerRemovalException;
use SpaethTech\WSL\Exceptions\DockerfileNotFoundException;
use SpaethTech\WSL\Exceptions\ReleaseNotSupportedException;
use SpaethTech\WSL\Exceptions\UnexpectedOutputException;
use SpaethTech\WSL\Exceptions\VersionNotSupportedException;

require_once __DIR__."/vendor/autoload.php";

final class RoboFile extends Tasks
{
    use MonoRepo\Tasks;

    private const GIT_PROVIDER      = "https://github.com";
    private const ORGANIZATION      = "spaethtech";
    private const MONOREPO_DIR      = "lib";
    private const REGEX_OWNER       = "/^[a-z0-9-]+$/";
    private const REGEX_NAME        = "/^[a-z0-9-]+$/";
    private const REGEX_NAMESPACE   = "/^[A-Z]+[A-Za-z0-9_]*$/";

    /**
     * @command lib:add
     *
     * Clones an existing repository into the monorepo, as a library
     *
     * @param string    $name       The name of the library
     *
     * @option string   $dir        The base directory for libraries, relative to this RoboFile
     * @option string   $force      Forces replacement of an existing library
     * @option string   $owner      The owner of the library
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function libraryAdd(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::MONOREPO_DIR,
        "force|f" => FALSE,
        "owner|o" => self::ORGANIZATION
    ])
    {
        $this->taskLibraryAdd($name)
            ->dir($options["dir"])
            ->owner($options["owner"])
            ->force($options["force"])
            ->run();
    }

    /**
     * @command lib:del
     *
     * Removes an existing library from the monorepo
     *
     * @param string    $name       The name of the library
     *
     * @option string   $dir        The base directory for libraries, relative to this RoboFile
     * @option string   $force      Forces replacement of an existing library
     * @option string   $owner      The owner of the library
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function libraryDel(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::MONOREPO_DIR,
        "force|f" => FALSE,
        "owner|o" => self::ORGANIZATION
    ])
    {
        $this->taskLibraryDel($name)
            ->dir($options["dir"])
            ->owner($options["owner"])
            ->force($options["force"])
            ->run();
    }

    /**
     * @command lib:doc
     *
     * Generates documentation for an existing library in the monorepo
     *
     * @param string    $name               The name of the library
     *
     * @option string   $dir                The base directory for libraries, relative to this RoboFile
     * @option string   $owner              The owner of the library
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function libraryDoc(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::MONOREPO_DIR,
        "owner|o" => self::ORGANIZATION
    ])
    {
        $path = "${options["dir"]}/$name";
        $name = "${options["owner"]}/$name";

        if (!file_exists($path))
            $this->error("Package not found!", TRUE);

        $full = realpath(PROJECT_DIR);

        $this->_exec(
            "docker run --rm ".
            "-v $full:/data ".
            "phpdoc/phpdoc ".
            "--directory /data/$path/src ".
            "--target /data/$path ".
            "--cache-folder /data/.cache/$name/.phpdoc/ ".
            "--title $name ".
            "--template=/data/lib/phpdoc-markdown/templates/markdown"
        );

    }

    /**
     * @command lib:new
     *
     * Creates a new library
     *
     * @param string $name                  The name of the library
     *
     * @option string $replace              Forces replacement of an existing library
     * @option string $template             The template to use when creating the library
     *
     * @return void
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function libraryNew(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::MONOREPO_DIR,
        "force|f" => FALSE,
        "owner|o" => self::ORGANIZATION
    ])
    {
        // IMPORTANT: Install GitHub CLI from https://cli.github.com/

        $this->taskLibraryNew($name)
            ->env("GH_TOKEN", "TESTING")
            ->dir($options["dir"])
            ->owner($options["owner"])
            ->force($options["force"])
            ->run();


        // git submodule add --name spaethtech/phpdoc-markdown lib/phpdoc-markdown

        // Branch master to main
        // cd lib/<package>
        // git branch -m master main && git push -u origin main && git symbolic-ref refs/remotes/origin/HEAD refs/remotes/origin/main
        // MANUAL: https://github.com/spaethtech/common/settings/branches
        // git push origin --delete master



    }


    private const WSL_DOCKER_DIR = PROJECT_DIR."/wsl/docker";
    private const WSL_DOCKER_PHP = self::WSL_DOCKER_DIR."/php";
    private const WSL_DOCKER_OUT = self::WSL_DOCKER_DIR."/images";


    /**
     * @param string $version The version of PHP to build
     *
     * @option bool $wsl            Import the image as a WSL distribution after a successful build
     *
     * @return void
     * @throws Exception
     */
    public function wslBuild(ConsoleIO $io, string $version,
        array $options = [
            "owner|o" => self::ORGANIZATION,
            "wsl|w" => false,
        ]
    )
    {
        $ver = new Version($version);
        $own = $io->input()->getOption("owner");
        $wsl = $io->input()->getOption("wsl");
        $dir = FileSystem::path(self::WSL_DOCKER_PHP);
        $out = FileSystem::path(self::WSL_DOCKER_OUT."/php-$ver.tar");

        // Configure individual versions as necessary...
        switch($ver->getMajorMinor())
        {
            case "7.4":
                $xdebug = ($ver->build >= 20) ? "3.1.6" : "3.0.4";
                break;
            case "8.0":
                $xdebug = ($ver->build >= 7) ? "3.1.6" : "3.0.4";
                break;
            case "8.1":
            case "8.2":
                $xdebug = "3.2.0";
                break;
            default:
                throw new VersionNotSupportedException("Specified PHP version is not currently supported!");
        }



        if (!($file = realpath("$dir/Dockerfile")))
            throw new DockerfileNotFoundException("Could not find the base Dockerfile!");

        if (($release = $ver->release) !== "" &&
            (!($dir = realpath("$dir/$release")) || !($file = realpath("$dir/Dockerfile"))))
            throw new ReleaseNotSupportedException("The specified release is not currently supported!");

        $command = join(" ", [
            "docker build",
            "--force-rm",
            "--tag \"${options["owner"]}/wsl-php:$ver\"",
            "--build-arg PHP_VERSION=\"$ver\"",
            "--build-arg XDEBUG_VERSION=\"$xdebug\"",
            "--file \"$file\"",
            "\"$dir\""
            //"."
        ]);

        $io->writeln("<fg=green>Building the PHP $ver (w/ Xdebug $xdebug)</>");
        $io->writeln("<fg=cyan> [EXEC] $command</>");
        passthru($command);

        // -- CREATE -----------------------------------------------------------
        $command = [
            "docker create",
            "\"${options["owner"]}/wsl-php:$ver\""
        ];
        $command = $this->sayCommand($command);
        exec($command, $output, $result);

        if  ($result !== 0)
            throw new ContainerCreationException("Container creation failed!");

        if (count($output) !== 1)
            throw new ContainerCreationException("Unexpected output!");

        $containerId = $output[0];

        // -- EXPORT -----------------------------------------------------------
        $output = FileSystem::path(self::WSL_DOCKER_OUT."/php-$ver.tar");

        if(!file_exists($outputDir = dirname($output)))
            mkdir($outputDir, 0755, true);

        $command = [
            "docker export",
            "--output \"$output\"",
            $containerId
        ];
        $command = $this->sayCommand($command);
        passthru($command);

        // -- REMOVE -----------------------------------------------------------

        $output = [];
        $this->execCommand("docker rm ${containerId}", $stdout, $stderr, true, true);

        //var_dump($stdout);
        //var_dump($stderr);

        //chdir($owd);
    }


    /**
     * @param array|string $command
     * @param array|null $stdout
     * @param array|null $stderr
     * @param bool $print
     * @param bool $compact
     * @param callable|null $filter
     *
     * @return int
     */
    private function execCommand($command, array &$stdout = null, array &$stderr = null, bool $print = false,
        bool $compact = false, callable $filter = null): int
    {
//        $prefix = " [EXEC]";
//        $padding = join("", array_fill(0, strlen($prefix), " "));

        $command = $this->sayCommand($command);
        $process = new Process();
        $exitcode = $process->execute($command);

        $stdout = $process->getOutput();
        $stderr = $process->getError();

        if ($print)
            $process->printOutput($this->output());

        return $exitcode;


//        $process = proc_open($command,[ 1 => [ "pipe", "w" ], 2 => [ "pipe", "w" ] ], $pipes);
//        $stdout = stream_get_contents($pipes[1]);
//        fclose($pipes[1]);
//        $stderr = stream_get_contents($pipes[2]);
//        fclose($pipes[2]);
//        $result = proc_close($process);
//
//        $stdout = ($stdout === false || $stdout === "") ? [] : explode("\n", $stdout);
//        $stderr = ($stderr === false || $stderr === "") ? [] : explode("\n", $stderr);
//
//        if ($compact)
//        {
//            $stdout = array_filter($stdout);
//            $stderr = array_filter($stderr);
//        }
//
//        if ($filter)
//        {
//            $stdout = array_filter($stdout, $filter);
//            $stderr = array_filter($stderr, $filter);
//        }
//
//        if ($print)
//        {
//            foreach ($stdout as $line)
//                $this->writeln("$padding $line");
//
//            foreach ($stderr as $line)
//                $this->writeln("$padding <bg=red>$line</>");
//        }
//
//        return $result;
    }




    /**
     * @param array|string $command
     *
     * @return void
     */
    private function sayCommand(/* ConsoleIO $io, */ $command, bool $multiline = true): string
    {
        $prefix = " [EXEC]";

        if (is_array($command))
        {
            if (count($command) == 0)
                return "";

            if (count($command) == 1)
            {
                $this->writeln("<fg=cyan>$prefix ${command[0]}</>");
                return $command[0];
            }

            if (!$multiline)
            {
                $joined = join(" ", $command);
                $this->writeln("<fg=cyan>$prefix $joined</>");
                return $joined;
            }

            $padding = join("", array_fill(0, strlen($prefix), " "));

            $this->writeln("<fg=cyan>$prefix ${command[0]}</>");
            for($i = 1; $i < count($command); $i++)
                $this->writeln("<fg=cyan>$padding ${command[$i]}</>");

            return join(" ", $command);
        }

        $this->writeln("<fg=cyan>$prefix $command</>");
        return $command;
    }


    #region HELPERS

    /**
     * @param string $question
     * @param bool $default
     *
     * @return bool
     */
    private function askYesNo(string $question, bool $default = FALSE): bool
    {
        do
        {
            $replace = $this->askDefault("$question [y/N]", $default ? "Y" : "N");
            $replace = strtoupper($replace);

        } while (!in_array($replace, [ "Y", "N" ]));

        return $replace === "Y";
    }

    /**
     * @param string $message               The warning message
     *
     * @return void
     */
    private function warning(string $message)
    {
        $this->writeln("<bg=yellow>$message</>");
    }

    /**
     * @param string $message               The error message
     * @param bool $die                     Optionally, die() after displaying the message.  Defaults to FALSE
     *
     * @return void
     */
    private function error(string $message, bool $die = FALSE)
    {
        $this->writeln("<bg=red>$message</>");
        if ($die) die();
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function normalize_path(string $path): string
    {
        return str_replace([ "\\", "/" ], DIRECTORY_SEPARATOR, $path);
    }




    /**
     * @param string $source
     * @param string $destination
     * @param array $variables
     *
     * @return void
     */
    private function template(string $source, string $destination, array $variables = [])
    {

        //$this->taskTemplate()->


        exit;

        $source = $this->normalize_path($source);
        $destination = $this->normalize_path($destination);

        $directory = new RecursiveDirectoryIterator($source);
        $iterator = new RecursiveIteratorIterator($directory);

        /** @var SPLFileInfo $info */
        foreach ($iterator as $info)
        {
            $file = $info->getFilename();
            $path = $info->getRealPath();

            if ($file == "." || $file == "..")
                continue;

            $contents = file_get_contents($path);
            $relative = str_replace($source, "", $path);

            if (preg_match("/^(.*)\.template\.(.*)$/", $relative, $matches))
            {
                if(count($matches) !== 3)
                {
                    $this->say("Skipping template file: $relative");
                    continue;
                }

                $relative = "${matches[1]}.${matches[2]}";

                $contents = preg_replace_callback("/\{\{(.*)}}/",
                    function(array $matches) use ($variables)
                    {
                        if (count($matches) !== 2)
                        {
                            $this->say("Skipping template variable: $matches[0]");
                            return $matches[0];
                        }

                        $variable = trim($matches[1]);

                        if (!array_key_exists($variable, $variables))
                        {
                            $this->writeln("<bg=yellow>Variable replacement not found for: $variable</>");
                            return $matches[0];
                        }

                        return $variables[$variable];
                    },
                    $contents
                );

                //echo $contents."\n";
                //echo "$relative\n";

                $this->writeln(" <bg=cyan;fg=white>[Templater]</> Templated $destination$relative");
            }
            else
            {
                $this->writeln(" <bg=cyan;fg=white>[Templater]</> Copied to $destination$relative");
            }

            //echo "$p\n";

            //echo "$relative\n";

            $current = $this->normalize_path("$destination$relative");
            $currentDir = dirname($current);

            if(!file_exists($currentDir))
                mkdir($currentDir, 0775, TRUE);

            file_put_contents($current, $contents);




            //echo "$current\n";
            //echo "$destination\n";
            //echo "$relative\n";
            //echo "$currentDir\n";
        }
    }


    #endregion




}
