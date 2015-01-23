<?php

class SimpleCache
{
    private $db = null;
    private $tableName = 'cache';

    function __construct($url) {
        $db = pg_connect($this->pgConnectionStringFromDatabaseUrl($url));
        if (!$db) {
            throw new Exception('Database connection error');
        }
        $this->db = $db;
        pg_query($this->db, sprintf("CREATE TABLE IF NOT EXISTS %s (data text, created_at timestamp DEFAULT NULL);", $this->tableName));
    }

    private function pgConnectionStringFromDatabaseUrl($url) {
        $url = parse_url($url);
        return sprintf("user=%s password=%s host=%s dbname=%s", $url['user'], $url['pass'], $url['host'], substr($url['path'], 1));
    }

    public function get() {
        $urls = array();
        $result = pg_query($this->db, sprintf("SELECT * FROM %s ORDER BY created_at ASC;", $this->tableName));
        if (pg_num_rows($result)) {
            while ($row = pg_fetch_array($result)) {
                $urls[] = $row;
            }
        }
        return $urls;
    }

    public function add($value, $datetime = '') {
        return pg_query($this->db, sprintf("INSERT INTO %s VALUES ('%s', %s);", $this->tableName, $value, empty($datetime) ? "now()" : "'".$datetime."'"));
    }

    public function set($values) {
        pg_query($this->db, sprintf("TRUNCATE TABLE %s;", $this->tableName));
        foreach ($values as $key => $value) {
            $this->add($value['data'], $value['created_at']);
        }
        return $values;
    }
}
