<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 28/03/2015
 * Time: 17:48
 */

namespace SeedSync\Commands;


use SeedSync\Config;
use SeedSync\Host;
use Symfony\Component\Console\Output\OutputInterface;

class Calendar implements CommandInterface {
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
        return false;
    }

    public function getCalendarEventType()
    {
        return null;
    }

    public function setCalendarEvent($calendarEvent){}

    /**
     * @param \SeedSync\Host $host
     */
    public function execute(Host $host)
    {
        $config = new Config();
        $calendarPath = __DIR__.'/..'.$config->getCalendarPath();
        $calendarUrl = $config->getCalendarUrl();
        file_put_contents($calendarPath,file_get_contents($calendarUrl));
    }
}