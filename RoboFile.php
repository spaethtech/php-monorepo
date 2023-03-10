<?php
/**
 * @noinspection PhpUnused
 * @noinspection PhpUnusedParameterInspection
 * @noinspection PhpUnusedPrivateMethodInspection
 */
declare(strict_types=1);

use Robo\Symfony\ConsoleIO;
use Robo\Tasks;
use SpaethTech\Robo\Task\MonoRepo;

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

        $template   = "multi-file";

//        if (!file_exists(PROJECT_DIR."/vendor/spaethtech/phpdoc-markdown"))
//            $this->taskComposerRequire()
//                ->dependency("spaethtech/phpdoc-markdown")
//                ->dev()
//                //->ignorePlatformRequirements("ext-xsl")
//                ->run();

        //$this->_exec("rm -rf $templatePath/.git");

        $this->_exec(
            "docker run --rm ".
            "-v $full:/data ".
            "phpdoc/phpdoc ".
            "--directory /data/$path/src ".
            "--target /data/$path ".
            "--cache-folder /data/.cache/$name/.phpdoc/ ".
            "--title $name ".
            "--template=/data/lib/phpdoc-markdown/templates/$template"
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
