<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 26/06/2015
 * Time: 08:43
 */

namespace SeedSync\Commands;


use SeedSync\DbConn;
use SeedSync\Host;
use Symfony\Component\Console\Output\OutputInterface;

interface CommandInterface {
    public function __construct(\PDO $db,OutputInterface $outputInterface);
    public function isModeDependant();
    public function getCalendarEventType();
    public function setCalendarEvent($calendarEvent);
    public function execute(Host $host);
}