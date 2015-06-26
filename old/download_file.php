<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 14/06/14
 * Time: 11:37
 */
require_once 'vendor/autoload.php';

class FileDownload {

    protected  $db;
    protected $host;

    protected $maxSpeed = 0;
    protected $speedPerFile = false;
    protected $maxSimDownloads;
    protected $ignoreSpeed = false;

    public function __construct($hostName){

        $this->db = \SeedSync\DbConn::getInstance()->get();

        try{
            $this->host = new \SeedSync\Host($this->db);
            $this->host->get($hostName);
        }
        catch (Exception $error){
            throw $error;
        }

        $this->maxSpeed = $this->host->getMaxSpeed();
        $this->maxSimDownloads = $this->host->getSimultaneousDownloads();

        $this->speedPerFile = $this->maxSpeed / $this->maxSimDownloads;
    }

    public function shouldRun()
    {
        $config = new \SeedSync\Config();
        $mode = $config->getMode();

        if($mode == \SeedSync\Config::MODE_ON){
            return false;
        }else if ($mode === \SeedSync\Config::MODE_CALENDAR){
            $scheduler = new \SeedSync\Scheduler();
            $scheduler->setCalendar(file_get_contents(__DIR__.'/calendar.ics'));

            $events = $scheduler->getEventType('download');

            $numberOfEvents = count($events);

            if($numberOfEvents == 0){
                echo date('d/m/Y H:i:s -').' No active events bye!'.PHP_EOL;
                return false;
            }

            foreach($events as $event){
                if(isset($event->host) == false || $event->host != $this->host->getHost()){
                    continue;
                }

                if(isset($event->ignoreSpeed) == true && $event->ignoreSpeed == true){
                    $this->ignoreSpeed = true;
                    $this->speedPerFile = false;
                }

                echo date('d/m/Y H:i:s -').' Events says lets download!'.PHP_EOL;

                return true;
            }
        }else{
            echo 'No need to do anything mode is ' . $mode.PHP_EOL;
            return false;
        }
    }

    public function downloadResumed(){
        $toResume = \SeedSync\Download::getOldStuff($this->db,$this->host->getHostId(),'RESUME');

        //stop if there are no downloads to resume
        if(count($toResume) <= 0){
            echo 'No downloads to resume'.PHP_EOL;
            return false;
        }

        $newDownload = $toResume[0];
        $this->downloadFile($newDownload);
        return true;
    }

    public function downloadNew(){

        $freeDownloadSlots = $this->getFreeSlots();

        if($freeDownloadSlots <= 0){
            echo "No free download slots".PHP_EOL;
            return false;
        }

        echo "There are $freeDownloadSlots free download slots".PHP_EOL;

        $newDownloads = \SeedSync\Download::getOldStuff($this->db,$this->host->getHostId(),'NEW');

        //if there is nothing to download
        if($newDownloads == null || is_array($newDownloads) == false || count($newDownloads) == 0){
            echo 'Nothing to download!';
            return false;
        }

        $newDownload = $newDownloads[0];

        echo 'Downloading ' . $newDownload->getFileName().PHP_EOL;

        $this->downloadFile($newDownload);
    }

    /**
     * @param \SeedSync\Download $download
     */
    protected function downloadFile($download){

        echo 'Downloading file at ';
        echo ($this->speedPerFile == false) ? 'max speed' : $this->speedPerFile.' Kbs';
        echo PHP_EOL;

        $download->setStatus('DOWNLOADING');
        echo 'Pid set'.PHP_EOL;
        $download->setDownloadPid();
        echo 'Status set'.PHP_EOL;
        $download->setReason('');

        $fileName = $download->getFileName();

        try{
            $download->setDateStarted();
            $file = new \SeedSync\File($this->host,$this->db);
            $file->downloadRemote($fileName,$this->speedPerFile);

            $percentageComplete  = $file->getPercentageComplete($fileName,$download->getFileSize());

            echo 'Percentage Complete: ' . $percentageComplete;

            if($percentageComplete >= 99.8)
            {
                $download->setDateComplete();
                $download->setStatus('COMPLETE');
                $file->moveComplete($fileName);
            }
            else
            {
                $file->removeTemp($fileName);
                $download->setStatus('FAILED');
                $download->setReason("Copied file was to small. It was only $percentageComplete of the original file");
            }
        }
        catch(\SeedSync\FileException $error){
            if(isset($file) == true){
                $file->removeTemp($fileName);
            }
            $download->setStatus('FAILED');
            $download->setReason($error->getMessage());
        }
        $download->setDownloadPid(0);
    }

    private function getFreeSlots(){
        $downloading = \SeedSync\Download::getOldStuff($this->db,$this->host->getHostId(),'DOWNLOADING');
        $downloading = array_merge($downloading,\SeedSync\Download::getOldStuff($this->db,$this->host->getHostId(),'PAUSED'));
        return $this->maxSimDownloads - count($downloading);
    }
}

if(isset($argv[1]) == false){
    echo 'No host provided'.PHP_EOL;
    exit;
}

$host = $argv[1];

$fileDownload = new FileDownload($host);

if($fileDownload->shouldRun() == false){
    exit;
}

$downloadResumed = $fileDownload->downloadResumed();

//when no downloads are resumed
if($downloadResumed === false){
    $fileDownload->downloadNew();
}