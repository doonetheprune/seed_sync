<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 05/04/14
 * Time: 21:41
 */

require_once 'vendor/autoload.php';

$db = \SeedSync\DbConn::getInstance(__DIR__.'/SeedSync.sdb')->get();

$output = array();
$outputType = (isset($argv[1]) == true) ? strtoupper($argv[1]) : 'CLI';
$action = $argv[2];

switch ($action){

    //lists downloads depending on type
    case 'list':
        $listType = (isset($argv[3]) == true) ?  strtoupper($argv[3]) : false;

        $hosts = \SeedSync\Host::getAllHosts($db);

        foreach($hosts as $host){
            $downloads = \SeedSync\Download::getAll($db,$host->getHostId(),$listType,'LastModified DESC');

            switch($listType){
                case 'DOWNLOADING':

                    $downloads = \SeedSync\Download::getAll($db,$host->getHostId(),'RESUME','LastModified DESC');
                    $downloads = array_merge($downloads,\SeedSync\Download::getAll($db,$host->getHostId(),'PAUSED','LastModified DESC'));
                    $downloads = array_merge($downloads,\SeedSync\Download::getAll($db,$host->getHostId(),'DOWNLOADING','LastModified DESC'));

                    foreach($downloads as $download){
                        $file = new \SeedSync\File($host,$db);
                        $percentageComplete = $file->getPercentageComplete($download->getFileName(),$download->getFileSize());
                        //convert bytes to 10mb or 10GB etc
                        $humanSize = $file->humanFileSize($download->getFileSize());
                        $key = $download->getId();
                        $dateStarted = date('H:i:s d/m/Y',$download->getDateStarted());

                        $timezone = new DateTimeZone('UTC');
                        $duration = new DateTime('now',$timezone);
                        $duration->setTimestamp(time() - $download->getDateStarted());
                        $duration = $duration->format('H:i:s');

                        //add to output array
                        $output[$key] = array('status' => $download->getStatus(), 'file' => $download->getFileName(),'size' => $humanSize,'pid' => $download->getDownloadPid(),'priority' => $download->getPriority(),'percentageComplete' => $percentageComplete,'host' => $host->getHost(),'dateStarted' => $dateStarted,'duration' => $duration);
                    }
                    break;

                case 'NEW':

                    $downloads = \SeedSync\Download::getAll($db,$host->getHostId(),$listType);

                    foreach($downloads as $download){
                        $file = new \SeedSync\File($host,$db);
                        //convert bytes to 10mb or 10GB etc
                        $humanSize = $file->humanFileSize($download->getFileSize());
                        $key = $download->getId();

                        $dateAdded = date('H:i:s d/m/Y',$download->getDateAdded());
                        $lastModified = date('H:i:s d/m/Y',$download->getLastModified());

                        //add to output array
                        $output[$key] = array('file' => $download->getFileName(),'size' => $humanSize,'priority' => $download->getPriority(),'host' => $host->getHost(),'dateAdded' => $dateAdded, 'lastModified' => $lastModified);
                    }
                    break;

                case 'FAILED':

                    $downloads = \SeedSync\Download::getAll($db,$host->getHostId(),$listType,'LastModified DESC');

                    foreach($downloads as $download){
                        $file = new \SeedSync\File($host,$db);
                        //convert bytes to 10mb or 10GB etc
                        $humanSize = $file->humanFileSize($download->getFileSize());
                        $key = $download->getId();

                        $dateStarted = date('H:i:s d/m/Y',$download->getDateStarted());
                        $lastModified = date('H:i:s d/m/Y',$download->getLastModified());
                        $duration = date('H:i:s',$download->getLastModified() - $download->getDateStarted());

                        //add to output array
                        $output[$key] = array('file' => $download->getFileName(),'size' => $humanSize,'priority' => $download->getPriority(),'host' => $host->getHost(),'reason' => $download->getReason(),'dateStarted' => $dateStarted, 'lastModified' => $lastModified,'duration' => $duration);
                    }
                    break;

                case 'COMPLETE':

                    $downloads = \SeedSync\Download::getAll($db,$host->getHostId(),$listType,'LastModified DESC');

                    foreach($downloads as $download){
                        $file = new \SeedSync\File($host,$db);
                        //convert bytes to 10mb or 10GB etc
                        $humanSize = $file->humanFileSize($download->getFileSize());
                        $key = $download->getId();

                        $dateStarted = date('H:i:s d/m/Y',$download->getDateStarted());
                        $dateComplete = date('H:i:s d/m/Y',$download->getDateComplete());

                        $timezone = new DateTimeZone('UTC');
                        $duration = new DateTime('now',$timezone);
                        $duration->setTimestamp($download->getDateComplete() - $download->getDateStarted());
                        $duration = $duration->format('H:i:s');

                        //add to output array
                        $output[$key] = array('file' => $download->getFileName(),'size' => $humanSize,'priority' => $download->getPriority(),'host' => $host->getHost(),'dateStarted' => $dateStarted, 'dateComplete' => $dateComplete,'duration' => $duration);
                    }
                    break;
            }
        }
        break;

    case 'setPriority':
        $file = $argv[3];
        $priority = $argv[4];

        try{
            $download = new \SeedSync\Download($db);
            $download->getById($file);
            $download->setPriority($priority);
        }
        catch(\SeedSync\DownloadException $error){
            $output['error'] = $error->getMessage();
        }
        break;

    case 'setStatus':
        $file = $argv[3];
        $status = strtoupper($argv[4]);

        try{
            $download = new \SeedSync\Download($db);
            $download->getById($file);
            $download->setStatus($status);
        }
        catch(\SeedSync\DownloadException $error){
            $output['error'] = $error->getMessage();
        }
        break;

    case 'deleteDownload':
        $file = $argv[3];
        $status = $argv[4];

        try{
            $download = new \SeedSync\Download($db);
            $download->getById($file);
            $download->delete();
        }
        catch(\SeedSync\DownloadException $error){
            $output['error'] = $error->getMessage();
        }
        break;

    case 'deleteRemoteFile':
        $file = $argv[3];

        try{
            $download = new \SeedSync\Download($db);
            $download->getById($file);
            $download->delete();
        }
        catch(\SeedSync\DownloadException $error){
            $output['error'] = $error->getMessage();
        }
        break;

    case 'resumeDownload' :
        $file = $argv[3];
        $download = new \SeedSync\Download($db);
        $download->getById($file);
        $download->setStatus('RESUME');
        break;

    case 'pauseDownload':

        $file = $argv[3];
        $download = new \SeedSync\Download($db);
        $download->getById($file);

        $fileName = escapeshellarg($download->getFileName());

        echo "ps -eo pid,command | grep $fileName | grep -v grep | awk '{print $1}'".PHP_EOL;

        exec("ps -eo pid,command | grep $fileName | grep -v grep | awk '{print $1}'",$rsyncPids);

        $rsyncPids[] = $download->getDownloadPid();

        foreach($rsyncPids as $pid){
            exec("touch ".__DIR__."/pids_to_kill/$pid.pid");
        }

        $download->setStatus('PAUSED');
        break;

    case 'stopDownload':
        $file = $argv[3];
        $download = new \SeedSync\Download($db);
        $download->getById($file);

        $fileName = escapeshellarg($download->getFileName());

        echo "ps -eo pid,command | grep '$fileName' | grep -v grep | awk '{print $1}'".PHP_EOL;

        exec("ps -eo pid,command | grep '$fileName' | grep -v grep | awk '{print $1}'",$rsyncPids);

        $rsyncPids[] = $download->getDownloadPid();

        foreach($rsyncPids as $pid){
            exec("touch ".__DIR__."/pids_to_kill/$pid.pid");
        }

        $download->setStatus('FAILED');
        $download->setReason('Stopped by user!');
        break;

    case 'percentageComplete':
        $file = $argv[3];

        try{
            $download = new \SeedSync\Download($db);
            $download->getById($file);
            $percentageComplete = $file->getPercentageComplete($download->getFile(),$download->getSize());
        }
        catch(\SeedSync\DownloadException $error){
            $output['error'] = $error->getMessage();
        }
        break;

    case 'getHost':
        $host = new \SeedSync\Host($db);
        $host->get($argv[3]);
        $row = array();
        $row['hostId'] = $host->getHostId();
        $row['localFolder'] = $host->getLocalFolder();
        $row['remoteFolder'] = $host->getRemoteFolder();
        $row['maxSpeed'] = $host->getMaxSpeed();
        $row['simultaneousDownloads'] = $host->getSimultaneousDownloads();
        $row['username'] = $host->getUser();
        $row['password'] = $host->getPassword();
        $output[] = $row;
        break;
    case 'setHost':

        $host = new \SeedSync\Host($db);
        $host->get($argv[3]);

        $property = $argv[4];
        $value = $argv[5];

        switch($property){
            case 'localFolder':
                $host->setLocalFolder($value);
                break;
            case 'remoteFolder':
                $host->setRemoteFolder($value);
                break;
            case 'maxSpeed':
                $host->setMaxSpeed($value);
                break;
            case 'simDownloads':
                $host->setSimultaneousDownloads($value);
                break;
            case 'username':
                $host->setUser($value);
                break;
            case 'password':
                $host->setPassword($value);
                break;
        }

        break;
    case 'truncate':
        $db->query('DELETE FROM '.\SeedSync\Download::TABLE);
        break;


}

if($outputType == 'CLI'){
    if(isset($output['error']) == true){
        echo $output['error'].PHP_EOL;
    }
    else{
        foreach($output as $line){
            echo implode(' ',$line).PHP_EOL;
        }
    }
}
else{
    echo json_encode($output);
}