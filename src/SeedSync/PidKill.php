<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 26/06/2015
 * Time: 11:58
 */

namespace SeedSync;


class PidKill {
    protected $db;

    public function __construct()
    {
        $this->db = DbConn::getInstance()->get();
    }

    public function addPid(Download $download)
    {
        $fileName = escapeshellarg($download->getFileName());

        exec("ps -eo pid,command | grep $fileName | grep -v grep | awk '{print $1}'",$pids);

        $pids[] = $download->getDownloadPid();

        foreach($pids as $pid){
            if($pid >= 1){
                $stmt = $this->db->prepare('INSERT INTO `PidsToKill` (`PID`) VALUES (:pid)');
                $stmt->execute(array('pid' => $pid));
            }
        }
    }

    public function killAll()
    {
        $stmt = $this->db->prepare('SELECT `PID` FROM `PidsToKill`');
        $stmt->execute();

        while($row = $stmt->fetch(\PDO::FETCH_OBJ)){
            exec("kill -9 " . $row->PID);
            $delStmt = $this->db->prepare('DELETE FROM `PidsToKill` WHERE `PID` = :pid');
            $res = $delStmt->execute(array('pid' => $row->PID));

            if($res === false){
                throw new \RuntimeException('Failed to delete pid from the database');
            }
        }
    }
}