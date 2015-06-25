<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 15/03/14
 * Time: 14:27
 */

namespace SeedSync;

use Ssh\Session;
use Ssh\Configuration;
use Ssh\Authentication\Password;

class Host
{
    const TABLE = 'Hosts';

    protected $db;
    protected $session;

    private $hostId;
    private $host;
    private $user;
    private $password;
    private $publicKey;
    private $remoteFolder;
    private $localFolder;
    private $localTemp;
    private $simultaneousDownloads;
    private $maxSpeed;
    private $active;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @param \PDO $db
     * @return Host[]
     * @throws \Exception
     */
    public static function getAllHosts(\PDO $db)
    {
        $stmt = $db->prepare("SELECT * FROM ".self::TABLE." WHERE Active = 1");
        $stmt->execute();

        $hosts = array();
        $dbHosts = $stmt->fetchAll(\PDO::FETCH_OBJ);

        foreach($dbHosts as $dbHost)
        {
            $host = new Host($db);
            $host->get($dbHost->Host);
            $hosts[$host->getHostId()] = $host;
        }
        return $hosts;
    }


    public function addHost($host,$user,$password,$remoteFolder,$localFolder,$localTemp,$maxSpeed,$simultaneousDownloads = 1,$active = true,$publicKey = 'NONE')
    {
        $id = time() + rand(1,9);
        $active = ($active === true) ? 1 : 0;
        $remoteFolder = (substr($remoteFolder,'-1') != '/') ? '/' : $remoteFolder;
        $localFolder = (substr($localFolder,'-1') != '/') ? '/' : $localFolder;
        $localTemp = (substr($localTemp,'-1') != '/') ? '/' : $localTemp;
        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE." (Host,User,Password,PublicKey,RemoteFolder,LocalFolder,LocalTemp,SimultaneousDownloads,MaxSpeed,Active) VALUES (:host,:user,:password,:publicKey,:remoteFolder,:localFolder,:localTemp,:simultaneousDownloads,:maxSpeed,:active)");
        $stmt->execute(array('host' => $host,'user' => $user,'password' => $password,'publicKey' => $publicKey ,'remoteFolder' => $remoteFolder,'localFolder' => $localFolder, 'localTemp' => $localTemp, 'simultaneousDownloads' => $simultaneousDownloads,'maxSpeed' => $maxSpeed,'active' => $active));
    }

    public function get($host)
    {
        $stmt = $this->db->prepare("SELECT * FROM ".self::TABLE." WHERE Host = :host");
        $stmt->execute(array('host' => $host));

        $host = $stmt->fetch(\PDO::FETCH_OBJ);

        if($host == false)
        {
            throw new \Exception('Could not locate host');
        }

        $this->hostId = $host->ID;
        $this->host = $host->Host;
        $this->user = $host->User;
        $this->password = $host->Password;
        $this->publicKey = $host->PublicKey;
        $this->remoteFolder = $host->RemoteFolder;
        $this->localFolder = $host->LocalFolder;
        $this->localTemp = $host->LocalTemp;
        $this->simultaneousDownloads = $host->SimultaneousDownloads;
        $this->maxSpeed = $host->MaxSpeed;
        $this->active = $host->Active;

        $this->session = $this->createSession();
    }

    /**
     * @return mixed
     */
    public function getHostId()
    {
        return $this->hostId;
    }

    /**
     * @return \Ssh\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $localFolder
     */
    public function setLocalFolder($localFolder)
    {
        $localFolder = (substr($localFolder,'-1') != '/') ? '/' : $localFolder;
        $this->saveColumn('LocalFolder',$localFolder);
        $this->localFolder = $localFolder;
    }

    /**
     * @return mixed
     */
    public function getLocalFolder()
    {
        return $this->localFolder;
    }

    /**
     * @param mixed $localTemp
     */
    public function setLocalTemp($localTemp)
    {
        $localTemp = (substr($localTemp,'-1') != '/') ? '/' : $localTemp;
        $this->saveColumn('LocalTemp',$localTemp);
        $this->localTemp = $localTemp;
    }

    /**
     * @return mixed
     */
    public function getLocalTemp()
    {
        return $this->localTemp;
    }

    /**
     * @param mixed $maxSpeed overall max speed in kbs
     */
    public function setMaxSpeed($maxSpeed)
    {
        $this->saveColumn('MaxSpeed',$maxSpeed);
        $this->maxSpeed = $maxSpeed;
    }

    /**
     * @return mixed overall max speed in kbs
     */
    public function getMaxSpeed()
    {
        return $this->maxSpeed;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->saveColumn('Password',$password);
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $publicKey
     */
    public function setPublicKey($publicKey)
    {
        $this->saveColumn('PublicKey',$publicKey);
        $this->publicKey = $publicKey;
    }

    /**
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param mixed $remoteFolder
     */
    public function setRemoteFolder($remoteFolder)
    {
        $remoteFolder = (substr($remoteFolder,'-1') != '/') ? '/' : $remoteFolder;
        $this->saveColumn('RemoteFolder',$remoteFolder);
        $this->remoteFolder = $remoteFolder;
    }

    /**
     * @return mixed
     */
    public function getRemoteFolder()
    {
        return $this->remoteFolder;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->saveColumn('User',$user);
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $active = ($active === true) ? 1 : 0;
        $this->saveColumn('Active',$active);
        $this->active = $active;
    }

    /**
     * @param mixed $simultaneousDownloads
     */
    public function setSimultaneousDownloads($simultaneousDownloads)
    {
        $this->saveColumn('SimultaneousDownloads',$simultaneousDownloads);
        $this->simultaneousDownloads = $simultaneousDownloads;
    }

    /**
     * @return mixed
     */
    public function getSimultaneousDownloads()
    {
        return $this->simultaneousDownloads;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    protected function saveColumn($columnName,$value)
    {
        $stmt = $this->db->prepare("UPDATE ".self::TABLE." SET $columnName  = :columnValue");
        $stmt->execute(array('columnValue' => $value));

        if($stmt->rowCount() == 0)
        {
            throw new \Exception('Could not update ' . $columnName);
        }
    }

    protected function createSession()
    {
        $configuration = new Configuration($this->host);
        $authentication = new Password($this->user, $this->password);
        return new Session($configuration, $authentication);
    }
}
