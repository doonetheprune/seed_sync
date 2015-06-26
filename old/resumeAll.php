<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 21/06/14
 * Time: 12:43
 */

require_once 'vendor/autoload.php';

$scheduler = new \SeedSync\Scheduler();
$scheduler->setCalendar(file_get_contents(__DIR__.'/calendar.ics'));

$events = $scheduler->getEventType('resume');

$numberOfEvents = count($events);

if($numberOfEvents == 0){
    exit;
}

$db = \SeedSync\DbConn::getInstance()->get();

$hosts = array();

foreach($events as $event){
    if(isset($event->host) == false){
        continue;
    }
    else if(strtoupper($event->host) == 'ALL'){
        $hosts = array_merge($hosts,$hosts = \SeedSync\Host::getAllHosts($db));
    }else{
        $host = new \SeedSync\Host($db);
        $host->get($event->host);
        $hosts[] = $host;
    }
}

if(count($hosts) == 0){
    echo date('d/m/Y H:i:s -').' No hosts defined!'.PHP_EOL;
    exit;
}

foreach($hosts as $host)
{
    $downloads = \SeedSync\Download::getAll($db,array('Status' => array('operator' => '=','value' => " 'PAUSED' " )));

    $numActive = count($downloads);

    echo date('d/m/Y H:i:s -')." Host {$host->getHost()} has $numActive paused downloads".PHP_EOL;

    foreach($downloads as $download){
        echo 'Resuming download ' . $download->getId().PHP_EOL;
        echo shell_exec('/usr/bin/php '.__DIR__."/gui.php cli resumeDownload {$download->getId()}").PHP_EOL;
    }
    echo '------------------------------------'.PHP_EOL;
}