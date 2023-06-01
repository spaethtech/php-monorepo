<?php
declare(strict_types=1);

namespace App\Robo\Task\PhpStorm;

enum Component
{
    case VCS;



    public function file(): string
    {
        return match($this) {
            Component::VCS => ".idea/vcs.xml",
        };
    }

    public function name(): string
    {
        return match($this) {
            Component::VCS => "VcsDirectoryMappings",
        };
    }


}
