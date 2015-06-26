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

    protected function __construct()
    {
        try {
            $config = parse_ini_file(__DIR__.'/../../config.ini');
            $this->db = new \PDO($config['DSN'].';dbname=SeedSync', $config['DB_USER'], $config['DB_PASS']);
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
    public static function getInstance ()
    {
        if (self::$instance == null)
        {
            self::$instance = new DbConn();
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