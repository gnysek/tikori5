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
        $this->_conn = new mysqli(
            'p:' .Core::app()->cfg('db/dbhost'),
            Core::app()->cfg('db/dbuser'),
            Core::app()->cfg('db/dbpass'),
            Core::app()->cfg('db/dbname')
        );

        if (mysqli_connect_error()) {
            throw new DbError('Nie można połączyć z MySqli: ' . mysqli_connect_error());
        }

        $this->_init = true;

//        $this->conn()->select_db(Core::app()->cfg('db/dbname'));

        if ($this->conn()->error) {
            throw new DbError('Nie wybrać tabeli: ' . $this->conn()->error);
        }
        Profiler::addLog(__CLASS__ . ' connected');
    }

    public function query($sql, $skip = '', $assoc = TRUE)
    {
        Profiler::addLog('SQL QUERY: <tt title="' . $sql . '">' . substr($sql, 0, 30) . '&hellip;</tt>');
        $this->_queries++;

        if (preg_match('/^(insert|update|delete|replace)/i', $sql)) {
            $result = $this->conn()->query($sql);
            Profiler::addLog('Exec finished');
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
                    while ($row = $result->fetch_object()) {
                        $record = new Record();
                        $record->import($row, false);
                        $return[] = $record;
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
