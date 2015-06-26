<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 28/03/2015
 * Time: 17:17
 */

require __DIR__.'/vendor/autoload.php';

use \SeedSync\SeedSyncCommand;
use \Symfony\Component\Console\Application;

$application = new Application();
$application->add(new SeedSyncCommand());
$application->run();