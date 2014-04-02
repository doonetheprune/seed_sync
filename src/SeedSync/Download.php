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
    const TABLE = 'DOWNLOADS';

    protected $db;

    private $hostId;
    private $fileName;
    private $fileSize;
    private $dateAdded;
    private $lastModified;
    private $priority;
    private $status;
    private $downloadPid;
    private $reason;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public static function getAll(\PDO $db, $hostId, $status = false)
    {
        $prepareSql = "SELECT * FROM ".self::TABLE." WHERE HostID = :hostId";
        $params = array('hostId' => $hostId);

        if($status != false)
        {
            $prepareSql = $prepareSql." AND Status = :status";
            $params['status'] = self::convertTextStatus($status);
        }

        $prepareSql = $prepareSql . ' ORDER BY Priority ASC';

        $stmt = $db->prepare($prepareSql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $preparedRows = array();

        foreach($rows as $row){
            $preparedRow = new Download($db);
            $preparedRow->get($row->File,$row);
            $preparedRows[] = $preparedRow;
        }
        return $preparedRows;
    }

    public function add($hostId,$fileName,$lastModified,$fileSize,$priority = '2')
    {
        $status = $this->convertTextStatus('NEW');
        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE." (HostID,File,FileSize,DateAdded,LastModified,Priority,Status,DownloaderPID) VALUES (:hostId,:file,:fileSize,:dateAdded,:latModified,:priority,:status,:downloaderPID)");
        $stmt->execute(array('hostId' => $hostId, 'file' => $fileName, 'fileSize' => $fileSize,'dateAdded' => time(), 'latModified' => $lastModified, 'status' => $status ,'priority' => $priority, 'downloaderPID' => '0'));
    }

    public function get($fileName,$download = null)
    {
        if($download == null) {

            $stmt = $this->db->prepare("SELECT * FROM ".self::TABLE." WHERE File = :file");
            $stmt->execute(array('file' => $fileName));

            $download = $stmt->fetch(\PDO::FETCH_OBJ);

            if($download == false || count($stmt->fetchAll()) == 0)
            {
                throw new DownloadException('Could not locate file in the database',400);
            }
        }

        $this->hostId = $download->HostID;
        $this->fileName = $fileName;
        $this->fileSize = $download->FileSize;
        $this->dateAdded = $download->DateAdded;
        $this->lastModified = $download->LastModified;
        $this->priority = $download->Priority;
        $this->status = $this->convertIntStatus($download->Status);
        $this->reason = $download->Reason;
        $this->downloadPid = $download->DownloaderPID;
    }

    public function getHostId()
    {
        return $this->hostId;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function dateAdded()
    {
        return $this->hostId;
    }

    /**
     * @param mixed $dateModified
     */
    public function setLastModified($dateModified)
    {
        $this->saveColumn('LastModified',$dateModified);
        $this->lastModified = $dateModified;
    }

    /**
     * @return mixed
     */
    public function getLastModified()
    {
        return $this->lastModified;
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
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $status = $this->convertTextStatus($status);
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
        $this->saveColumn('Reason',$reason);
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
        if($downloadPid == null)
        {
            $downloadPid = getmypid();
        }

        $this->saveColumn('DownloaderPID',$downloadPid);
        $this->downloadPid = $downloadPid;
    }

    /**
     * @return mixed
     */
    public function getDownloadPid()
    {
        return $this->downloadPid;
    }

    protected function saveColumn($columnName,$value)
    {
        $stmt = $this->db->prepare("UPDATE ".self::TABLE." SET $columnName  = :columnValue WHERE File = :file AND HostID = :host");
        $stmt->execute(array('file' => $this->fileName, 'host' => $this->hostId,'columnValue' => $value));

        if($stmt->rowCount() == 0)
        {
            throw new DownloadException('Could not update ' . $columnName);
        }
    }

    protected function convertTextStatus($status)
    {
        switch($status){
            case 'DOWNLOADING':
                return 1;
                break;
            case 'NEW':
                return 2;
                break;
            case 'FAILED':
                return 3;
                break;
            case 'COMPLETE':
                return 4;
                break;
            default:
                throw new DownloadException('Unknown download status',400);
                break;
        }
    }

    protected function convertIntStatus($status)
    {
        switch($status){
            case 1:
                return 'DOWNLOADING';
                break;
            case 2:
                return 'NEW';
                break;
            case 3:
                return 'FAILED';
                break;
            case 4:
                return 'COMPLETE';
                break;
            default:
                throw new DownloadException('Unknown download status',400);
                break;
        }
    }
}