<?php

/**
 * Description of DB
 *
 * @author user
 */
class DbPDO extends DBAbstract
{

    public function connect()
    {
        if (!$this->_init) {
            $this->_init = TRUE;

            if (Core::app()->cfg('db/dblink') === null) {
                throw new DbError('Database not yet configured!');
            }

            try {
                $this->_conn = new PDO(
                    Core::app()->cfg('db/dblink'),
                    Core::app()->cfg('db/dbuser'),
                    Core::app()->cfg('db/dbpass')
                );

                $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->_conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            } catch (PDOException $e) {
                throw new DbError('Nie można połączyć z PDO: ' . $e->getMessage());
            }

            if (!$this->connected()) {
                throw new DbError('Nie można połączyć z PDO');
            }
        }
        return FALSE;
    }


    /**
     *
     * @param string $sql   Sql query to perform
     * @param string $skip  Not used for now
     * @param bool   $assoc Assoc or not?
     *
     * @throws DbError
     * @return Collection
     */
    public function query($sql, $skip = '', $assoc = TRUE)
    {
        Profiler::addLog('SQL QUERY: <kbd title="' . $sql . '">' . substr($sql, 0, 30) . '&hellip;</kbd>');

        $this->_queries++;

        if (preg_match('/^(insert|update|delete|replace)/i', $sql)) {
            $result = $this->_conn->exec($sql);
            Profiler::addLog('Exec finished');
            return true;
        } else {
            $result = $this->conn()->query($sql);
            Profiler::addLog('Query finished');
        }

        if (!$result) {
            if ($this->conn()->errorCode()) {
                throw new DbError($sql . '<br/>' . implode(': ', $this->conn()->errorInfo()));
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
                throw new DbError('SQL ERROR ' . $sql . '<br/>' . $this->conn()->errorInfo());
            }
        }
    }

    public function update($sql)
    {
        $result = $this->conn()->exec($sql);
        if ($result === FALSE) {
            throw new DbError('Błąd zapytania<br/>' . $sql . '<br/>' . var_export($this->conn()->errorInfo(), true));
        } else {
            return $result;
        }
    }

    public function lastId()
    {
        return $this->conn()->lastInsertId();
    }


    public function protect($string)
    {
        return $this->conn()->quote($string);
    }

}
