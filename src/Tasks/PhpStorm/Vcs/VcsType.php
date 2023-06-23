<?php
declare(strict_types=1);

namespace App\Tasks\PhpStorm\Vcs;


enum VcsType: string
{
    case GIT = "Git";

    case MERCURIAL = "hg4idea";

    case NONE = "";

    case PERFORCE = "Perforce";

    case SUBVERSION = "svn";


    public static function nearest(string $possible): self
    {
        $possible = strtolower($possible);

        return match($possible)
        {
            "git" => VcsType::GIT,
            "svn", "subversion" => VcsType::SUBVERSION,
            "hg4", "mercurial" => VcsType::MERCURIAL,
            "perforce" => VcsType::PERFORCE,
            default => die("Could not determine the VCS type using: $possible!")
        };
    }

}
