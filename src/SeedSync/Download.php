<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 15/03/14
 * Time: 14:23
 */

namespace SeedSync;

class DownloadException extends \Exception{};
class Download
{
    const TABLE = 'Downloads';

    const STATUS_NEW = 'NEW';
    const STATUS_PAUSED = 'PAUSED';
    const STATUS_RESUME = 'RESUME';
    const STATUS_DOWNLOADING = 'DOWNLOADING';

    protected $db;

    private $id;
    private $hostId;
    private $fileName;
    private $fileSize;
    private $dateAdded;
    private $lastModified;
    private $priority;
    private $status;
    private $downloadPid;
    private $reason;
    private $dateStarted;
    private $dateComplete;

    private $validStatuses = array('DOWNLOADING','NEW','FAILED','COMPLETE','PAUSED','RESUME');

    public function __construct(\PDO $db, \stdClass $download = null)
    {
        $this->db = $db;

        //if download object has been supplied
        if($download != null){
            $this->get($download);
        }
    }

    /**
     * @param \PDO $db
     * @param $hostId
     * @param array $criteria
     * @param string $order
     * @return Download[]
     */
    public static function getAll(\PDO $db,$criteria = array(), $order = 'Priority ASC, LastModified DESC')
    {
        $numericStatusSql = "CASE `Status` WHEN 'DOWNLOADING' THEN 0	WHEN 'PAUSED' THEN 1 WHEN 'NEW' THEN 2	WHEN 'COMPLETE' THEN 3 ELSE 4 END AS 'NumericStatus'";
        $prepareSql = "SELECT d.*, $numericStatusSql FROM ".self::TABLE." d ";

        if(count($criteria) >= 1){
            $prepareSql .= 'WHERE ';

            foreach($criteria as $column => $value){
                $prepareSql .= $column .' ' . $value['operator'] . ' ' . $value['value'] . ' AND ';
            }
            $prepareSql = trim($prepareSql,'AND ');
        }

        $prepareSql = $prepareSql . ' ORDER BY `NumericStatus` ASC, '.$order;

        $stmt = $db->prepare($prepareSql);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);


        /**
         * @var \SeedSync\Download[]
         */
        $preparedRows = array();

        foreach($rows as $row){
            $preparedRow = new Download($db,$row);
            $preparedRows[] = $preparedRow;
        }

        return $preparedRows;
    }


    /**
     * @param $db
     * @param Host $host
     * @param $status
     * @return Download[]
     */
    public static function getDownloadsForHost($db,$host,$status)
    {
        return Download::getAll($db,array('Status' => array('operator' => '=','value' => " '$status' " ),'HostID' => array('operator' => '=','value' => " '{$host->getHostId()}' " )));
    }

    public static function getOldStuff($db,$hostId,$status)
    {
        return Download::getAll($db,array('Status' => array('operator' => '=','value' => " '$status' " ),'HostID' => array('operator' => '=','value' => " '$hostId' " )));
    }

    public function add($hostId,$fileName,$fileSize,$priority = '2')
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE." (HostID,File,FileSize,DateAdded,LastModified,Priority,Status,DownloaderPID) VALUES (:hostId,:file,:fileSize,:dateAdded,:latModified,:priority,:status,:downloaderPID)");
        $stmt->execute(array('hostId' => $hostId, 'file' => $fileName, 'fileSize' => $fileSize,'dateAdded' => $now, 'latModified' => $now, 'status' => 'NEW' ,'priority' => $priority, 'downloaderPID' => '0'));
    }

    public function delete()
    {
        $stmt = $this->db->prepare("DELETE FROM ".self::TABLE." WHERE File = :file");
        $stmt->execute(array('file' => $this->fileName));

        if($stmt->rowCount() == 0){
            throw new DownloadException('Could not delete the download.');
        }
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ".self::TABLE." WHERE ID = :id");
        $stmt->execute(array('id' => $id));

        $download = $stmt->fetch(\PDO::FETCH_OBJ);

        if($download == false)
        {
            throw new DownloadException('Could not locate file in the database with id ' . $id,400);
        }

        $this->get($download);
    }

    public function getByName($fileName)
    {
        $stmt = $this->db->prepare("SELECT * FROM ".self::TABLE." WHERE File = :file");
        $stmt->execute(array('file' => $fileName));

        $download = $stmt->fetch(\PDO::FETCH_OBJ);

        if($download == false){
            throw new DownloadException('Could not locate file in the database',400);
        }

        $this->get($download);
    }

    public function pauseDownload()
    {
        $pidKill = new PidKill();
        $pidKill->addPid($this);
        $this->setStatus(self::STATUS_PAUSED);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getHostId()
    {
        return $this->hostId;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @return mixed
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @param mixed $dateComplete
     */
    public function setDateComplete($dateComplete = null)
    {
        $dateComplete = ($dateComplete == null) ? date('Y-m-d H:i:s') : $dateComplete;
        $this->saveColumn('DateComplete',$dateComplete);
        $this->dateComplete = $dateComplete;
    }

    /**
     * @return mixed
     */
    public function getDateComplete()
    {
        return $this->dateComplete;
    }

    /**
     * @param mixed $dateStarted
     */
    public function setDateStarted($dateStarted = null)
    {
        $dateStarted = ($dateStarted == null) ? date('Y-m-d H:i:s') : $dateStarted;
        $this->saveColumn('DateStarted',$dateStarted);
        $this->dateStarted = $dateStarted;
    }

    /**
     * @return mixed
     */
    public function getDateStarted()
    {
        return $this->dateStarted;
    }

    /**
     * @param mixed $size
     */
    public function setFileSize($size)
    {
        $this->saveColumn('FileSize',$size);
        $this->fileSize = $size;
    }

    /**
     * @return mixed
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param $status
     * @throws DownloadException on invalid status
     */
    public function setStatus($status)
    {
        //check the status is valid
        if(in_array($status,$this->validStatuses) === false){
            throw new DownloadException('Invalid download status',400);
        }

        $this->saveColumn('Status',$status);
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $priority
     */
    public function setPriority($priority)
    {
        $this->saveColumn('Priority',$priority);
        $this->priority = $priority;
    }

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $reason
     */
    public function setReason($reason)
    {
        $this->saveColumn('Reason',$reason,false);
        $this->reason = $reason;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param mixed $downloadPid
     */
    public function setDownloadPid($downloadPid = null)
    {
        $requireUpdate = false;
        if(isset($downloadPid) === false){
            $downloadPid = getmypid();
            $requireUpdate = true;
        }

        $this->saveColumn('DownloaderPID',$downloadPid,$requireUpdate);
        $this->downloadPid = $downloadPid;
    }

    /**
     * @return mixed
     */
    public function getDownloadPid()
    {
        return $this->downloadPid;
    }

    protected  function get($download = null)
    {
        $this->id = $download->ID;
        $this->hostId = $download->HostID;
        $this->fileName = $download->File;
        $this->fileSize = $download->FileSize;
        $this->dateAdded = strtotime($download->DateAdded);
        $this->lastModified = strtotime($download->LastModified);
        $this->priority = $download->Priority;
        $this->status = $download->Status;
        $this->reason = $download->Reason;
        $this->downloadPid = $download->DownloaderPID;
        $this->dateStarted = strtotime($download->DateStarted);
        $this->dateComplete = strtotime($download->DateComplete);
    }

    protected function saveColumn($columnName,$value,$requireUpdate = true)
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE ".self::TABLE." SET $columnName  = :columnValue, LastModified = :lastModified WHERE ID = :id AND HostID = :host");
        $stmt->execute(array('id' => $this->id, 'host' => $this->hostId,'columnValue' => $value,'lastModified' => $now));

        if($stmt->rowCount() == 0 && $requireUpdate == true)
        {
            throw new DownloadException('Could not update ' . $columnName);
        }
    }
}