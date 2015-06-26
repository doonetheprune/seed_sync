<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 25/06/2015
 * Time: 22:05
 */

namespace SeedSync;


class Config {

    const MODE_ON = 'ON';
    const MODE_CALENDAR = 'CALENDAR';
    const MODE_OFF = 'OFF';

    protected $db;

    protected $calendarPath = null;
    protected $calendarUrl = null;
    protected $mode = null;


    public function __construct()
    {
        $this->db = DbConn::getInstance()->get();
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        if($this->mode === null){
            $this->mode = strtoupper($this->getProperty('Mode'));
        }
        return $this->mode;
    }

    public function setMode($newMode)
    {
        $this->setProperty('Mode',$newMode);
        $this->mode = $newMode;
        return $this->mode;
    }

    /**
     * @return mixed
     */
    public function getCalendarUrl()
    {
        if($this->calendarUrl === null){
            $this->calendarUrl = $this->getProperty('CalendarURL');
        }

        return $this->calendarUrl;
    }

    /**
     * @return mixed
     */
    public function getCalendarPath()
    {
        if($this->calendarPath === null){
            $this->calendarPath = $this->getProperty('CalendarPath');
        }

        return $this->calendarPath;
    }

    protected function getProperty($propertyName)
    {
        $stmt = $this->db->prepare('SELECT `PropertyValue` FROM `SeedSync`.`Config` WHERE `PropertyName` = :propertyName');
        $res = $stmt->execute(array('propertyName' => $propertyName));

        if($res === false){
            throw new \RuntimeException("Failed to get property $propertyName, there is an error in the query");
        }

        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        if($row === false){
            throw new \InvalidArgumentException('No config value exists for ' . $propertyName);
        }

        return $row->PropertyValue;
    }

    protected function setProperty($propertyName,$propertyValue)
    {
        $stmt = $this->db->prepare('UPDATE `SeedSync`.`Config` SET `PropertyValue` = :newValue WHERE `PropertyName` = :propertyName');
        $res = $stmt->execute(array('propertyName' => $propertyName,'newValue' => $propertyValue));
    }
}