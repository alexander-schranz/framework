<?php

declare(strict_types=1);

namespace Spiral\App\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\BootloadManager\BootloadManager;
use Spiral\Bootloader\Auth\HttpAuthBootloader;

class AuthBootloader extends Bootloader
{
    public function init(BootloadManager $bootloadManager): void
    {
        $bootloadManager->bootload([HttpAuthBootloader::class]);
    }
}
