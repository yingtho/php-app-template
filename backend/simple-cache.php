<?php

class SimpleCache
{
    protected $db = null;

    function __construct($url) {
        function pg_connection_string_from_database_url($url) {
            extract(parse_url($url));
            return "user=$user password=$pass host=$host dbname=".substr($path, 1);
        }
        $db = pg_connect(pg_connection_string_from_database_url($url));
        unset($url);
        if (!$db) {
            echo 'Database connection error.';
            exit;
        }
        $this->db = $db;
        pg_query("CREATE TABLE IF NOT EXISTS cache (data text, created_at timestamp DEFAULT NULL);");
    }

    public function get() {
        $urls = array();
        $result = pg_query($this->db, "SELECT * FROM cache");
        if (pg_num_rows($result)) {
            while ($row = pg_fetch_array($result)) {
                $urls[] = $row;
            }
        }
        return $urls;
    }

    public function add($value, $datetime = '') {
        return pg_query($this->db, "INSERT INTO cache VALUES ('".$value."', ".(empty($datetime) ? "now()" : "'".$datetime."'").");");
    }

    public function set($values) {
        pg_query($this->db, "TRUNCATE TABLE cache;");
        foreach ($values as $key => $value) {
            $this->add($value['data'], $value['created_at']);
        }
        return $values;
    }
}
