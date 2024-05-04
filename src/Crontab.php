<?php

namespace Tungnt\Crontab;

use Tungnt\Admin\Extension;

class Crontab extends Extension
{
    public $name = 'crontab';

    public $migrations = __DIR__.'/../migrations/';

    public $menu = [
        'title' => 'crontab',
        'path'  => 'crontab',
        'icon'  => 'fa-gears',
    ];
}
