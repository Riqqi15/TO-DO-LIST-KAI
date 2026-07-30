<?php

namespace App\Domain\Workspace\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
