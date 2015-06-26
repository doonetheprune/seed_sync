<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 28/03/2015
 * Time: 17:47
 */

namespace SeedSync\Commands;

use SeedSync\DbConn;
use SeedSync\File;
use SeedSync\FileException;
use SeedSync\Host;
use Symfony\Component\Console\Output\OutputInterface;

class Download implements CommandInterface {

    protected $db;
    protected $output;

    protected  $calendarEvent;

    protected $maxSimDownloads;

    protected $maxSpeed = 0;
    protected $ignoreSpeed = false;
    protected $speedPerFile = false;

    public function __construct(\PDO $db,OutputInterface $output)
    {
        $this->db = $db;
        $this->output = $output;
    }

    public function isModeDependant()
    {
        return true;
    }

    public function getCalendarEventType()
    {
        return 'download';
    }

    public function setCalendarEvent($calendarEvent)
    {
        $this->calendarEvent = $calendarEvent;
    }

    /**
     * @param \SeedSync\Host $host
     */
    public function execute(Host $host)
    {
        $this->output->writeln('<info>Doing download stuff for '.$host->getHost().' </info>');

        $this->maxSimDownloads = $host->getSimultaneousDownloads();

        $this->setDownloadSpeeds($host);

        if($this->checkDownloadsForStatus($host,\SeedSync\Download::STATUS_RESUME) === false){
            $this->output->writeln('<info>No downloads to resume </info>');
        }

        if($this->checkDownloadsForStatus($host,\SeedSync\Download::STATUS_NEW) === false){
            $this->output->writeln('<info>There are no new downloads either </info>');
        }
    }

    /**
     * @param \SeedSync\Host $host
     */
    protected function setDownloadSpeeds($host)
    {
        $this->maxSpeed = $host->getMaxSpeed();

        if(isset($this->calendarEvent->ignoreSpeed) == true && $this->calendarEvent->ignoreSpeed == true){
            $this->ignoreSpeed = true;
            $this->speedPerFile = false;
        }else{
            $this->ignoreSpeed = false;
            $this->speedPerFile = $this->maxSpeed / $this->maxSimDownloads;
        }
    }

    protected function checkDownloadsForStatus($host,$status)
    {
        $downloads = \SeedSync\Download::getDownloadsForHost($this->db,$host,$status);

        if(count($downloads) >= 1){
            $newDownload = $downloads[0];
            $this->downloadFile($host,$newDownload);
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param \SeedSync\Host $host
     * @param \SeedSync\Download $download
     */
    protected function downloadFile($host,$download){

        $this->output->writeln('<info>Downloading file at '.($this->speedPerFile == false) ? 'max speed' : $this->speedPerFile.' Kbs'.'</info>');

        $download->setStatus('DOWNLOADING');
        $this->output->writeln('<info>Status Set</info>');
        $download->setDownloadPid();
        $this->output->writeln('<info>PID Set</info>');
        $download->setReason('');

        $fileName = $download->getFileName();

        try{
            $download->setDateStarted();
            $file = new File($host,$this->db);
            $file->downloadRemote($fileName,$this->speedPerFile);

            $percentageComplete  = $file->getPercentageComplete($fileName,$download->getFileSize());

            $this->output->writeln('<info> Percentage Complete: ' . $percentageComplete.'</info>');

            if($percentageComplete >= 99.8){
                $download->setDateComplete();
                $download->setStatus('COMPLETE');
                $file->moveComplete($fileName);
            } else {
                $file->removeTemp($fileName);
                $download->setStatus('FAILED');
                $download->setReason("Copied file was to small. It was only $percentageComplete of the original file");
            }
        }
        catch(FileException $error){
            if(isset($file) == true){
                $file->removeTemp($fileName);
            }
            $download->setStatus('FAILED');
            $download->setReason($error->getMessage());
        }
        $download->setDownloadPid(0);
    }


    /**
     *                 if(isset($event->ignoreSpeed) == true && $event->ignoreSpeed == true){
    $this->ignoreSpeed = true;
    $this->speedPerFile = false;
    }
     */
}