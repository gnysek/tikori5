<?php

/**
 * Description of DB
 *
 * @author user
 */
class Db
{

    private static $_init = FALSE;
    private static $_queries = 0;

    /**
     * @var PDO
     */
    private static $_conn = FALSE;

    public static function connect()
    {
        if (!self::$_init) {
            self::$_init = TRUE;

            if (Core::app()->cfg('db/dblink') === null) {
                throw new DbError('Database not yet configured!');
            }

            try {
                self::$_conn = new PDO(
                    Core::app()->cfg('db/dblink'),
                    Core::app()->cfg('db/dbuser'),
                    Core::app()->cfg('db/dbpass')
                );

                self::$_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$_conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            } catch (PDOException $e) {
                throw new DbError('Nie można połączyć z PDO: ' . $e->getMessage());
                return FALSE;
            }

            if (!self::connected()) {
                throw new DbError('Nie można połączyć z PDO');
                return FALSE;
            }
        }
    }

    public static function conn()
    {
        if (!self::$_init) {
            self::connect();
        }

        return self::$_conn;
    }

    public static function connected()
    {
        return (bool)self::conn();
    }

    public static function queries()
    {
        return self::$_queries;
    }

    /**
     *
     * @param type $sql
     * @param type $skip
     * @param type $assoc
     *
     * @return Collection
     */
    public static function query($sql, $skip = '', $assoc = TRUE)
    {
        Log::addLog('SQL QUERY: <tt title="' . $sql . '">' . substr($sql, 0, 30) . '&hellip;</tt>');

        self::$_queries++;

        if (preg_match('/^(insert|update|delete|replace)/i', $sql)) {
            $result = self::$_conn->exec($sql);
            Log::addLog('Exec finished');
            return true;
        } else {
            $result = self::conn()->query($sql);
            Log::addLog('Query finished');
        }

        if (!$result) {
            if (self::conn()->errorCode()) {
                throw new DbError($sql . '<br/>' . implode(': ', self::conn()->errorInfo()));
            }
            return NULL;
        } else {
            if ($result instanceof PDOStatement) {
                $return = array();
                if ($result->rowCount()) {
//					$return = new Collection();
                    while (($row = $result->fetch(($assoc == true) ? PDO::FETCH_ASSOC : PDO::FETCH_NUM)) !== false) {
                        $record = new Record();
                        $record->import($row, false);
//						foreach ($row as $k => $v) {
//							$reserved->push(str_replace($skip, '', $k), $v);
//						}
                        $return[] = $record;
//						$return->push($reserved);
                    }
                    return $return;
                } else {
//					return new Collection();
                    return new Record();
                }
            } else {
                throw new DbError('SQL ERROR ' . $sql . '<br/>' . self::conn()->errorInfo());
                return NULL;
            }
        }
    }

    public static function update($sql)
    {
        $result = self::conn()->exec($sql);
        if ($result === FALSE) {
            throw new DbError('Błąd zapytania<br/>' . $sql . '<br/>' . var_export(self::conn()->errorInfo(), true));
            return NULL;
        } else {
            return $result;
        }
    }

    public static function lastId()
    {
        return self::conn()->lastInsertId();
    }

    public static function close()
    {
        self::$_conn = NULL;
    }

    public static function protect($string)
    {
        return self::conn()->quote($string);
    }

    /**
     * @return Sql
     */
    public static function sql()
    {
        return new Sql();
    }

}
