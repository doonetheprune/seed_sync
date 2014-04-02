<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 15/03/14
 * Time: 22:39
 */

require_once 'vendor/autoload.php';

$db = \SeedSync\DbConn::getInstance(__DIR__.'/SeedSync.sdb')->get();

$hosts =\SeedSync\Host::getAllHosts($db);

if(count($hosts) == 0){
    echo 'No hosts defined!';
}

foreach($hosts as $host)
{
    $file = new \SeedSync\File($host,$db);
    $file->scanRemote();
}