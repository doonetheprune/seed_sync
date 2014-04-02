<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 15/03/14
 * Time: 22:40
 */

    require_once 'vendor/autoload.php';

    //Stops all downloads scripts running at the same time
    sleep(rand(0,10));

    $db = \SeedSync\DbConn::getInstance(__DIR__.'/SeedSync.sdb')->get();

    $hosts =\SeedSync\Host::getAllHosts($db);

    try{
        $host = new \SeedSync\Host($db);
        $host->get($argv[1]);
    }
    catch (Exception $error){
        echo $error->getMessage().PHP_EOL;
        exit;
    }

    $maxSimDownloads = $host->getSimultaneousDownloads();
    $maxSpeed = $host->getMaxSpeed();

    if($maxSpeed >= 1){
        $speedPerFile = $maxSpeed / $maxSimDownloads;
    }
    else{
        $speedPerFile = false;
    }

    $downloading = \SeedSync\Download::getAll($db,$host->getHostId(),'DOWNLOADING');

    $freeDownloadSlots =  $maxSimDownloads - count($downloading);

    if($freeDownloadSlots <= 0)
    {
        exit;
    }

    $newDownloads = \SeedSync\Download::getAll($db,$host->getHostId(),'NEW');

    //if there is nothing to download
    if($newDownloads == null || is_array($newDownloads) == false || count($newDownloads) == 0){
        exit;
    }

    $newDownload = array_slice($newDownloads,0,1);
    $newDownload = $newDownloads[0];

    $newDownload->setStatus('DOWNLOADING');
    $newDownload->setDownloadPid();

    $fileName = $newDownload->getFileName();

    try{
        $file = new \SeedSync\File($host,$db);
        $file->downloadRemote($fileName,$speedPerFile);

        $percentageComplete  = $file->getPercentageComplete($fileName,$newDownload->getFileSize());

        echo 'Percentage Complete: ' . $percentageComplete;

        if($percentageComplete >= 99.8)
        {
            $newDownload->setStatus('COMPLETE');
            $file->moveComplete($fileName);
        }
        else
        {
            $file->removeTemp($fileName);
            $newDownload->setStatus('FAILED');
            $newDownload->setReason("Copied file was to small. It was only $percentageComplete of the original file");
        }
    }
    catch(\SeedSync\FileException $error){
        $file->removeTemp($fileName);
        $newDownload->setStatus('FAILED');
        $newDownload->setReason($error->getMessage());
    }
    $newDownload->setDownloadPid(0);
