<?php /** @noinspection PhpUnused */
declare(strict_types=1);

use Robo\Symfony\ConsoleIO;
use Robo\Tasks;
use SpaethTech\Robo\Task\MonoRepo;

require_once __DIR__."/vendor/autoload.php";

final class RoboFile extends Tasks
{
    use MonoRepo\Tasks;


    private const DEFAULT_GIT_PROVIDER  = "https://github.com";
    private const DEFAULT_ORGANIZATION  = "spaethtech";

    private const DEFAULT_PACKAGE_DIR   = "lib";


    //use Templating\Tasks;

    private const REGEX_PACKAGE_NAME        = "/^[a-z0-9-]+$/";
    private const REGEX_PACKAGE_NAMESPACE   = "/^[A-Z]+[A-Za-z0-9_]*$/";

    /**
     * Creates a
     *
     * @param string $name                  The name of the package
     * @param string|null $description      An optional description of the package
     * @param string|null $namespace        The package namespace
     *
     * @option string $replace              Forces replacement of an existing package
     * @option string $template             The template to use when creating the package
     *
     * @return void
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function packageCreate(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::DEFAULT_PACKAGE_DIR,
        "force|f" => FALSE,
        "owner|o" => self::DEFAULT_ORGANIZATION
    ])
    {
        // IMPORTANT: Install GitHub CLI from https://cli.github.com/

        // git submodule add --name spaethtech/phpdoc-markdown lib/phpdoc-markdown

        // Branch master to main
        // cd lib/<package>
        // git branch -m master main && git push -u origin main && git symbolic-ref refs/remotes/origin/HEAD refs/remotes/origin/main
        // MANUAL: https://github.com/spaethtech/common/settings/branches
        // git push origin --delete master



    }



    /**
     * Clones an existing package from GitHub to the monorepo
     *
     * @param string    $name               The name of the package
     *
     * @option string   $dir                The base directory for packages, relative to this RoboFile
     * @option string   $force              Forces replacement of an existing package
     * @option string   $owner              The owner of the package
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function packageAdd(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::DEFAULT_PACKAGE_DIR,
        "force|f" => FALSE,
        "owner|o" => self::DEFAULT_ORGANIZATION
    ])
    {
        $this->taskPackageAdd($name)
            ->dir($options["dir"])
            ->owner($options["owner"])
            ->force($options["force"])
            ->run();
    }


    /**
     * Removes an existing package from the monorepo
     *
     * @param string    $name               The name of the package
     *
     * @option string   $dir                The base directory for packages, relative to this RoboFile
     * @option string   $force              Forces replacement of an existing package
     * @option string   $owner              The owner of the package
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function packageRemove(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::DEFAULT_PACKAGE_DIR,
        "force|f" => FALSE,
        "owner|o" => self::DEFAULT_ORGANIZATION
    ])
    {
        $this->taskPackageRemove($name)
            ->dir($options["dir"])
            ->owner($options["owner"])
            ->force($options["force"])
            ->run();
    }

    /**
     * Removes an existing package from the monorepo
     *
     * @param string    $name               The name of the package
     *
     * @option string   $dir                The base directory for packages, relative to this RoboFile
     * @option string   $owner              The owner of the package
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function packageDoc(ConsoleIO $io, string $name, array $options = [
        "dir|d" => self::DEFAULT_PACKAGE_DIR,
        "owner|o" => self::DEFAULT_ORGANIZATION
    ])
    {
        $path = "${options["dir"]}/$name";
        $name = "${options["owner"]}/$name";

        if (!file_exists($path))
            $this->error("Package not found!", TRUE);

        $full = realpath(PROJECT_DIR);

        $dependency = "onspli/phpdoc-markdown";
        $template   = "$dependency/templates/public-onefile";

        //$templateSource = "https://github.com/dmarkic/phpdoc3-template-md.git";


        if (!file_exists(PROJECT_DIR."/vendor/$template"))
            $this->taskComposerRequire()
                ->dependency($dependency)
                ->dev()
                //->ignorePlatformRequirements("ext-xsl")
                ->run();

        //$this->taskGitStack()
        //    ->cloneShallow($templateSource, $templatePath = PROJECT_DIR."/templates/dmarkic")
        //    ->run();

        //$this->_exec("rm -rf $templatePath/.git");

        $this->_exec(
            "docker run --rm ".
            "-v $full:/data ".
            "phpdoc/phpdoc ".
            "--directory /data/$path/src ".
            "--target /data/$path ".
            "--cache-folder /data/.cache/$name/.phpdoc/ ".
            "--title $name ".
            //"--template=/data/vendor/$template"
            //"--template=/data/templates/dmarkic/themes/md"
            "--template=/data/templates/phpdoc/multi-file"
            //"--template=/data/templates/phpdoc/github/data/templates/contao"
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
