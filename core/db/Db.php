<?php

/**
 * Description of DB
 *
 * @author user
 */
class Db {

	private static $_init = FALSE;

	/**
	 * @var PDO
	 */
	private static $_conn = FALSE;

	public static function connect() {
		if (!self::$_init) {
			self::$_init = TRUE;

			try {
				self::$_conn = new PDO(Core::app()->cfg('dblink'), Core::app()->cfg('dbuser'), Core::app()->cfg('dbpass'));
			} catch (PDOException $e) {
				throw new DbError('Nie można połączyć z PDO ' . $e->getMessage());
				return FALSE;
			}

			if (!self::connected()) {
				throw new DbError('Nie można połączyć z PDO');
				return FALSE;
			}
		}
	}

	public static function conn() {
		if (!self::$_init) {
			self::connect();
		}

		return self::$_conn;
	}

	public static function connected() {
		return (bool) self::conn();
	}

	/**
	 *
	 * @param type $sql
	 * @param type $skip
	 * @param type $assoc
	 * @return Collection 
	 */
	public static function query($sql, $skip = '', $assoc = TRUE) {
		$result = self::conn()->query($sql);
		if (!$result) {
			if (self::conn()->errorCode()) {
				throw new DbError($sql . '<br/>' . implode(': ', self::conn()->errorInfo()));
			}
			return NULL;
		} else {
			if ($result instanceof PDOStatement) {
				$return = array();
				if ($result->rowCount()) {
					$return = new Collection();

					while (( $row = $result->fetch(( $assoc == true ) ? PDO::FETCH_ASSOC : PDO::FETCH_NUM) ) !== false) {
						$reserved = new Data();
						foreach ($row as $k => $v) {
							$reserved->push(str_replace($skip, '', $k), $v);
						}
						$return->push($reserved);
					}
					return $return;
				} else {
					return new Collection();
				}
			} else {
				throw new DbError('SQL ERROR ' . $sql . '<br/>' . self::conn()->errorInfo());
				return NULL;
			}
		}
	}

	public static function update($sql) {
		$result = self::conn()->exec($sql);
		if ($result === FALSE) {
			throw new DbError('Błąd zapytania<br/>' . $sql . '<br/>' . var_export(self::conn()->errorInfo(), true));
			return NULL;
		} else {
			return $result;
		}
	}

	public static function lastId() {
		return self::conn()->lastInsertId();
	}

	public static function close() {
		self::$_conn = NULL;
	}

	public static function protect($string) {
		return self::conn()->quote($string);
	}

	/**
	 * @return Sql 
	 */
	public static function sql() {
		return new Sql();
	}

}
