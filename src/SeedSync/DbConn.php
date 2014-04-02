<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 15/03/14
 * Time: 21:45
 */

namespace SeedSync;


class DbConn {

    private static $instance;
    protected $db;

    protected function __construct($dbPath)
    {
        try {
            $this->db = new \PDO('sqlite:'.$dbPath);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        catch(\PDOException $e){
            echo $e->getMessage();
        }
    }

    /**
     * Get connection to sqllite db
     * @param $dbPath
     * @return DbConn
     */
    public static function getInstance ($dbPath)
    {
        if (self::$instance == null)
        {
            self::$instance = new DbConn($dbPath);
        }
        return self::$instance;
    }

    /**
     * @return \PDO
     */
    public function get()
    {
        return $this->db;
    }
} 