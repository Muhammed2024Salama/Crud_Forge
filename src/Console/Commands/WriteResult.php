<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Console\Commands;

enum WriteResult
{
    case Written;
    case Skipped;
    case Error;
}
