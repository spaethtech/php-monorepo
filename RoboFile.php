<?php
/**
 * @noinspection PhpUnused
 * @noinspection PhpUnusedParameterInspection
 * @noinspection PhpUnusedPrivateMethodInspection
 */
declare(strict_types=1);

use App\Robo\Task\PhpStorm\Vcs\VcsType;
use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Symfony\ConsoleIO;
use Robo\Tasks;
use SpaethTech\Support\FileSystem;
use SpaethTech\Support\Process;
use SpaethTech\Support\Version;

require_once __DIR__."/vendor/autoload.php";

final class RoboFile extends Tasks
{
    //use MonoRepo\Tasks;
    use App\Robo\Task\PhpStorm\loadTasks;

    private const DEFAULT_GIT_URL   = "https://github.com";
    private const DEFAULT_MOD_ORG   = "spaethtech";
    private const DEFAULT_MOD_DIR   = "lib";

    private const REGEX_ORG_NAME    = "/^[a-z0-9-]+$/";
    private const REGEX_MOD_NAME    = "/^[a-z0-9-]+$/";

    private const REGEX_NAMESPACE   = "/^[A-Z]+[A-Za-z0-9_]*$/";

    /**
     * @command mod:add
     *
     * Clones an existing repository into the monorepo, as a submodule
     *
     * @param string $name  The name of the submodule
     *
     * @option string $dir  The submodules directory, relative to this RoboFile
     * @option string $org  The organization/owner of the repository
     *
     * @noinspection PhpUnusedParameterInspection
     * @throws TaskException
     */
    public function modAdd(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::DEFAULT_MOD_DIR,
        "org|o" => self::DEFAULT_MOD_ORG
    ]): void
    {
        /** @noinspection DuplicatedCode */
        $dir = $io->input()->getOption("dir");
        $org = $io->input()->getOption("org");

        if (!preg_match(self::REGEX_ORG_NAME, $org))
            die("Invalid organization name, must match: ".self::REGEX_ORG_NAME);

        if (!preg_match(self::REGEX_MOD_NAME, $name))
            die("Invalid repository name, must match: ".self::REGEX_MOD_NAME);

        $lib = "$dir/$name";
        $mod = "$org/$name";
        $url = self::DEFAULT_GIT_URL."/$mod";

        if (file_exists($lib))
            die("Submodule has already been added!");

        exec("git ls-remote $url -q 2>&1 /dev/null", $output, $result);
        if ($result > 0)
            die("A valid Git repository could not be located at: $url\n");

        $result = $this->taskExecStack()
            ->executable("git")
            ->exec("reset")
            ->exec("submodule add --name $mod $url $lib")
            ->exec("add .gitmodules")
            ->exec("add $lib")
            ->exec("commit -m \"Added submodule $mod to repository\"")
            ->run();

        if ($result->wasSuccessful())
            $this->taskVcs()->add($lib, "Git")->run();
    }

    /**
     * @command mod:del
     *
     * Removes a submodule from the monorepo
     *
     * @param string $name  The name of the submodule
     *
     * @option string $dir  The submodules directory, relative to this RoboFile
     * @option string $org  The organization/owner of the repository
     *
     * @noinspection PhpUnusedParameterInspection
     * @throws TaskException
     */
    public function modDel(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::DEFAULT_MOD_DIR,
        "org|o" => self::DEFAULT_MOD_ORG
    ]): void
    {
        /** @noinspection DuplicatedCode */
        $dir = $io->input()->getOption("dir");
        $org = $io->input()->getOption("org");

        if (!preg_match(self::REGEX_ORG_NAME, $org))
            die("Invalid organization name, must match: ".self::REGEX_ORG_NAME);

        if (!preg_match(self::REGEX_MOD_NAME, $name))
            die("Invalid repository name, must match: ".self::REGEX_MOD_NAME);

        $lib = "$dir/$name";
        $mod = "$org/$name";
        $url = self::DEFAULT_GIT_URL."/$mod";

        if (file_exists($lib) && !$this->askYesNo("Delete submodule $mod?"))
            return;

        // NOTE: IF the module directory does not exist, we still run the Tasks.
        // This is to clean up any dangling files and/org config.

        $this->taskExecStack()
            ->executable("git")
            ->exec("reset")
            ->exec("submodule deinit -f $lib")
            ->run();

        $this->taskExecStack()
            ->executable("git")
            ->exec("rm --cached $lib")
            ->run();

        $file = __DIR__."/.gitmodules";

        $pattern = /** @lang RegExp */
            "|^\[submodule \"(?<name>$mod)\"]$".
            "\s*path = (?<path>$lib)$".
            "\s*url = (?<url>$url)$\s*|m";

        $contents = file_get_contents($file);
        $contents = preg_replace($pattern, "", $contents);
        file_put_contents($file, $contents);

        $this->taskExecStack()
            ->executable("git")
            ->exec("add $lib")
            ->exec("add .gitmodules")
            ->exec("commit -m \"Removed submodule $mod from repository\"")
            ->run();

        $this->taskExecStack()
            ->executable("bash")
            ->exec("-c 'rm -rf $lib .git/modules/$mod'")
            ->run();

        $this->taskVcs()->del($lib)->run();
    }

    /**
     * @command mod:doc
     *
     * Generates documentation for an submodule in the monorepo
     *
     * @param string    $name               The name of the library
     *
     * @option string   $dir                The base directory for libraries, relative to this RoboFile
     * @option string   $owner              The owner of the library
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function modDoc(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::DEFAULT_MOD_DIR,
        "org|o" => self::DEFAULT_MOD_ORG
    ]): void
    {
        $org = $io->input()->getOption("org");
        $dir = $io->input()->getOption("dir");
        $mod = "$org/$name";
        $lib = "$dir/$name";

        if (!file_exists($lib))
            die("Submodule not found at: $lib");

        $full = realpath(PROJECT_DIR);

        if(!file_exists("$full/$dir/php-phpdoc"))
            die("Submodule spaethtech/php-phpdoc was not found, ".
                "but can be added using the command:\n".
                "\n".
                "    robo mod:add php-phpdoc".
                "\n"
            );

        $this->_exec(
            "docker run --rm ".
            "-v $full:/data ".
            "phpdoc/phpdoc ".
            "--directory /data/$lib/src ".
            "--target /data/$lib ".
            "--cache-folder /data/.cache/$mod/.phpdoc/ ".
            "--title $mod ".
            "--template=/data/lib/php-phpdoc/templates/markdown"
        );

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
     * @param string $message The warning message
     *
     * @return void
     */
    private function warning(string $message): void
    {
        $this->writeln("<bg=yellow>$message</>");
    }

    /**
     * @param string $message The error message
     *
     * @return void
     */
    private function error(string $message): void
    {
        $this->writeln("<bg=red>$message</>");
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

