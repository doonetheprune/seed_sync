<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 15/03/14
 * Time: 22:39
 */

require_once 'vendor/autoload.php';

$db = \SeedSync\DbConn::getInstance()->get();

$hosts = \SeedSync\Host::getAllHosts($db);

if(is_array($hosts) == false){
    echo 'No hosts defined!';
    exit;
}


foreach($hosts as $host){
    $file = new \SeedSync\File($host,$db);
    $file->scanRemote();
}