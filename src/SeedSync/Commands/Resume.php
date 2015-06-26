<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 26/06/2015
 * Time: 11:15
 */

namespace SeedSync\Commands;


use SeedSync\Download;
use SeedSync\Host;
use Symfony\Component\Console\Output\OutputInterface;

class Resume implements CommandInterface{
    protected $db;
    protected $output;

    protected  $calendarEvent;

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
        return 'resume';
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
        $downloads = Download::getDownloadsForHost($this->db,$host,Download::STATUS_PAUSED);

        $numActive = count($downloads);

        $this->output->writeln('<info>'.date('d/m/Y H:i:s -')." Host {$host->getHost()} has $numActive paused downloads </info>");

        foreach($downloads as $download){
            $this->output->writeln('<info>Resuming download ' . $download->getId().'</info>');
            $download->setStatus(Download::STATUS_RESUME);
        }
    }
}