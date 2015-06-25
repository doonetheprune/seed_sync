<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 15/03/14
 * Time: 14:27
 */

namespace SeedSync;

use \Ssh\Session;

class FileException extends \Exception{};
class File
{
    protected $host;
    protected $session;
    protected $sftp;
    protected $db;

    public function __construct(Host $host,$db)
    {
        $this->host = $host;
        $this->session = $host->getSession();
        $this->sftp = $this->session->getSftp();
        $this->db = $db;
    }

    public function scanRemote()
    {
        $hostId = $this->host->getHostId();
        $remotePath = $this->host->getRemoteFolder();

        $files = $this->sftp->listDirectory(trim($remotePath,'/'),true);
        $files = $files['files'];

        var_dump($files) . PHP_EOL;

        $files = $this->removePathPrefix($remotePath,$files);

        foreach($files as $file){

            if(strpos($file,'/AUTO/') === 0){
                continue;
            }

            try{
                $dbFile = new Download($this->db);
                $dbFile->getByName($file);
            }
            catch(DownloadException $error){
                $fileInfo = $this->getFileInfo($remotePath.$file);
                $dbFile->add($hostId,$file,$fileInfo->size);
            }
        }
    }

    public function downloadRemote($fileName,$limit,$type = 'rsync')
    {
        $host = $this->host->getHost();
        $user = $this->host->getUser();
        $remotePath = $this->host->getRemoteFolder();
        $tempLocal = $this->host->getLocalTemp();

        $this->createLocalFolderStructure($tempLocal,$fileName);

        $remoteFile = escapeshellarg($remotePath.$fileName);
        $localFile = escapeshellarg($tempLocal.$fileName);

        if($type == 'rsync'){
            $command = $this->buildRsyncSshCommand($user,$host,$remoteFile,$localFile,$limit);
        }
        else{
            $command = $this->buildSshCommand($user,$host,$remoteFile,$localFile,$limit);
        }

        exec($command);

        if(file_exists($tempLocal.$fileName) == false){
            throw new FileException('File was not copied to NAS');
        }
    }

    public function moveComplete($fileName)
    {
        $tempLocal = $this->host->getLocalTemp();
        $completeFolder = $this->host->getLocalFolder();

        $this->createLocalFolderStructure($completeFolder,$fileName);

        @rename($tempLocal.$fileName,$completeFolder.$fileName);

        if(file_exists($completeFolder) == false){
            throw new FileException('File was not moved to complete folder');
        }
    }

    public function removeTemp($fileName)
    {
        $tempLocal = $this->host->getLocalTemp().$fileName;

        //nothing to do
        if(file_exists($tempLocal) == false){
            return;
        }

        unlink($tempLocal);
    }

    public function removeRemote($fileName)
    {
        //@TODO remove the remote file
    }

    public function getPercentageComplete($fileName,$originalSize)
    {
        $localFile = $this->host->getLocalTemp().$fileName;


        if(($rSyncTemp = $this->getRsyncTempFileName($this->host->getLocalTemp(),$fileName) ) !== false){
            $downloadedFileSize = filesize($rSyncTemp);
        }
        else if(file_exists($localFile) !== false){
            $downloadedFileSize = filesize($localFile);
        }
        else{
            return 0;
        }

        if($downloadedFileSize == 0 || $originalSize == 0){
            return 9999;
        }

        $percentage = $downloadedFileSize / $originalSize * 100;
        return round($percentage,2);
    }

    public function humanFileSize($size)
    {
        if ($size >= 1073741824) {
            $fileSize = round($size / 1024 / 1024 / 1024,1) . 'GB';
        } elseif ($size >= 1048576) {
            $fileSize = round($size / 1024 / 1024,1) . 'MB';
        } elseif($size >= 1024) {
            $fileSize = round($size / 1024,1) . 'KB';
        } else {
            $fileSize = $size . ' bytes';
        }
        return $fileSize;
    }

    protected function readFolder($folderName,$folder)
    {
        print_r($folder);
        $files = array();

        if(is_array($folder['files'] ) == true)
        {
            foreach($folder['files'] as $file)
            {
                $files[] = $folderName.'/'.$file;
            }
        }

        if(is_array($folder['directories'] ) == true)
        {
            foreach($folder['directories'] as $dirName => $dir)
            {
                $subFolderFiles = $this->readFolder($folderName.'/'.$dirName,$dir);
                $files = array_merge($files,$subFolderFiles);
            }
        }

        return $files;
    }

    protected function removePathPrefix($pathPrefix,$files)
    {
        $pathPrefix = substr($pathPrefix,1);
        $newFiles = array();
        foreach($files as $file){
            $newFiles[] = str_replace($pathPrefix,'',$file);
        }
        return $newFiles;
    }

    private function getFileInfo($filePath)
    {
        $rawFileInfo = $this->sftp->stat($filePath);

        $fileInfo = new \stdClass();
        $fileInfo->size = $rawFileInfo['size'];
        $fileInfo->lastModified = $rawFileInfo['mtime'];
        return $fileInfo;
    }

    private function createLocalFolderStructure($folderPath,$file)
    {
        if(strpos($file,'/') === false)
        {
            return;
        }

        $folderPath = $folderPath.substr($file,0,strrpos($file,'/'));

        if(file_exists($folderPath) == false)
        {
            mkdir($folderPath,0777,true);
        }
    }

    private function buildSshCommand($user,$host,$remoteFile,$localFile,$limit)
    {
        //add limit param if set
        $commandParams = ($limit != false) ? "-l ".($limit * 8)." " :" ";

        //set encryption type
        $commandParams = $commandParams."-c blowfish -C ";

        return "scp $commandParams \"$user@$host:$remoteFile\" $localFile";
    }

    private function buildRsyncSshCommand($user,$host,$remoteFile,$localFile,$limit)
    {
        $commandParams = ($limit != false) ? "--bwlimit=".$limit." " :" ";

        $commandParams = $commandParams."-L -z --partial --inplace --rsh=ssh ";

        echo "rsync $commandParams \"$user@$host:$remoteFile\" $localFile";

        return "rsync $commandParams \"$user@$host:$remoteFile\" $localFile";
    }

    private function getRsyncTempFileName ($tempDir,$fileName)
    {
        $result = exec("ls $tempDir.$fileName.*");

        if(strpos($result,': No such file or directory') === false){
            return false;// $result;
        }
        else{
            return false;
        }
    }
} 