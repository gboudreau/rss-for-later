<?php

class DB {
	private $handle;

	public function connect() {
		$host = Config::DB_HOST;
		$password = Config::DB_PWD;

		ini_set('mysql.connect_timeout', 10);
		$this->handle = mysql_connect($host, Config::DB_USER, $password);
		if (!$this->handle) {
			throw new Exception("Can't connect to the database. Please try again later. Error: " . mysql_error());
		}
		$result = mysql_select_db(Config::DB_NAME);
		if (!$result) {
			throw new Exception("Database isn't available. Please try again later. Error: " . mysql_error());
		}
        //self::execute("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");
		mysql_set_charset('utf8');
	}
	
	public static function sortArgs($a, $b) {
		if (strlen($a) == strlen($b)) {
			return 0;
		}
		return strlen($a) > strlen($b) ? -1 : 1;
	}

	public static function execute($q, $args = array(), $escape=TRUE) {
		uksort($args, array('DB', 'sortArgs'));
        if ($escape) {
            foreach ($args as $key => $value) {
                // mysql_real_escape_string will only escape caracters that would cause problems in mysql strings, so we need to make sure the result is used in a string, thus why we force the single quotes around it.
                $q = preg_replace("/'?([^\\s']*)" . ":$key" . "([^\\s'),]*)'?/", '\'${1}' . mysql_real_escape_string($value) . '${2}\'', $q);
            }
        } else {
            foreach ($args as $key => $value) {
                $q = str_replace(":$key", $value, $q);
            }
        }
		if (DEBUGSQL) echo "$q<br/>\n";
		$r = mysql_query($q);
		if (!$r) {
			throw new Exception("Can't execute query: $q; error: " . mysql_error(), mysql_errno());
		}
		return $r;
	}

	public static function insert($q, $args = array()) {
		$r = static::execute($q, $args);
		return static::lastInsertedId();
	}

	public static function getFirst($q, $args = array()) {
		$r = static::execute($q, $args);
		return mysql_fetch_object($r);
	}
	public static function getFirstValue($q, $args = array()) {
		$r = static::execute($q, $args);
		$row = mysql_fetch_array($r);
		if (!is_array($row)) {
			return FALSE;
		}
		return array_shift($row);
	}

	public static function getAll($q, $args = array(), $escape=TRUE) {
		$r = static::execute($q, $args, $escape);
		$rows = array();
		while ($row = mysql_fetch_object($r)) {
			$rows[] = $row;
		}
		return $rows;
	}
	public static function getAllValues($q, $args = array()) {
		$r = static::execute($q, $args);
		$values = array();
		while ($row = mysql_fetch_array($r)) {
			if (!is_array($row)) {
				return FALSE;
			}

			$values[] = array_shift($row);
		}
		return $values;
	}

	public static function lastInsertedId() {
		return mysql_insert_id();
	} 

    public static function getLocalTZ($field) {
        return "CONVERT_TZ($field, '+00:00', '" . date('P') . "') AS $field"."_local";
    }
}
?>