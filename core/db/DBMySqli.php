<?php


class DbMySqli extends DBAbstract
{

    /**
     * @var mysqli
     */
    protected $_conn;

    public function connect()
    {
        Profiler::addLog(__CLASS__ . ' connecting');

        if (!class_exists('mysqli')) {
            throw new DbError('MySqli disabled');
        }

        mysqli_report(MYSQLI_REPORT_STRICT);

        try {
            $this->_conn = new mysqli(
                Core::app()->cfg('db/dbhost'),
                Core::app()->cfg('db/dbuser'),
                Core::app()->cfg('db/dbpass'),
                Core::app()->cfg('db/dbname')
            );
        } catch (mysqli_sql_exception $e) {
            throw new DbError('Can\'t connect to database: ' . mysqli_connect_error());
        }

        if (mysqli_connect_error()) {
            throw new DbError('Can\'t connect to database: ' . mysqli_connect_error());
        }

        $this->_init = true;

//        $this->conn()->select_db(Core::app()->cfg('db/dbname'));

        if ($this->conn()->error) {
            throw new DbError('Nie wybrać tabeli: ' . $this->conn()->error);
        }

        if (!$this->conn()->set_charset('utf8')) {
            throw new DbError('Cannot set encoding to UTF-8');
        }

        Profiler::addLog(__CLASS__ . ' connected');
    }

    /**
     * @param        $sql
     * @param string $skip
     * @param bool   $assoc
     * @return array|bool|Record
     * @throws DbError
     */
    public function query($sql, $skip = '', $assoc = TRUE)
    {
        Profiler::addLog('SQL QUERY: <kbd title="' . $sql . '">' . $sql . '</kbd>', Profiler::LEVEL_SQL);
        $this->_queries++;
        $this->_queryList[] = $sql;

        if (preg_match('/^(insert|update|delete|replace|alter)/i', $sql)) {
            $result = $this->conn()->query($sql);
            Profiler::addLog('Exec finished');
            if ($result === false) {
                throw new DbError($sql . '<br/>' . $this->conn()->error);
            }
            return true;
        } else {
            $result = mysqli_query($this->conn(), $sql);
            Profiler::addLog('Query finished');
        }


        if (!$result instanceof mysqli_result) {
//            if (mysqli_error($this->conn())) {
            throw new DbError($sql . '<br/>' . $this->conn()->error);
//            }
//            return NULL;
        } else {
            if ($result instanceof mysqli_result) {
                $return = array();
                if ($result->num_rows) {
                    while ($row = $result->fetch_object('Record')) {
                        /* @var $row Record */
                        if ($assoc == false) {
                            $row->removeAssocKeys();
                        }
                        $return[] = $row;
                    }
                    Profiler::addLog('Fetch finished');
                    return $return;
                } else {
                    return new Record();
                }
            } else {
                throw new DbError('SQL ERROR ' . $sql . '<br/>' . mysqli_error($this->conn()));
            }
        }
    }

    public function update($sql)
    {
        return $this->query($sql);
    }

    public function lastId()
    {
        return mysqli_insert_id($this->conn());
    }

    public function protect($string)
    {
        if (is_array($string)) {
            $elements = array();
            foreach ($string as $element) {
                $elements[] = $this->protect($element);
            }
            return implode(', ', $elements);
        }

        if (!is_string($string)) {
            return $string;
        }
        //return mysqli_escape_string($this->conn(), $string);
        $string = str_replace("'", "''", $string);
        $string = str_replace('\\', '\\\\', $string);
        return '\'' . $string . '\'';
    }

    /**
     * @return mysqli
     */
    public function conn()
    {
        return parent::conn();
    }

}
