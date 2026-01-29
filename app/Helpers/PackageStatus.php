<?php

namespace App\Helpers;

class PackageStatus
{
    const DRAFT = 0;
    const ACTION_REQUIRED = 1;
    const IN_REVIEW = 2;

    const READY_TO_SEND = 3;
    const CONSOLIDATE = 4;
}
