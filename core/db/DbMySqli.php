<?php


class DbMySqli extends DBAbstract
{

    /**
     * @var mysqli
     */
    protected $_conn;

    public function __construct($cfg = array())
    {
        if (!empty($cfg)) {
            $required = array('dbhost', 'dbuser', 'dbpass', 'dbname');
            if (count(array_intersect_key(array_flip($required), $cfg)) === count($required)) {
                foreach ($required as $key) {
                    $this->_config[$key] = $cfg[$key];
                }
            }
        }

        if (empty($this->_config)) {
            $this->_config = array(
                'dbhost' => Core::app()->cfg('db/dbhost'),
                'dbuser' => Core::app()->cfg('db/dbuser'),
                'dbpass' => Core::app()->cfg('db/dbpass'),
                'dbname' => Core::app()->cfg('db/dbname'),
            );
        }
    }

    public function connect()
    {
        Profiler::addLog(__CLASS__ . ' connecting');

        if (!class_exists('mysqli')) {
            throw new DbError('MySqli disabled');
        }

        mysqli_report(MYSQLI_REPORT_STRICT);

        try {
            $this->_conn = new mysqli(
                $this->_config['dbhost'],
                $this->_config['dbuser'],
                $this->_config['dbpass'],
                $this->_config['dbname']
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

        if (!$this->conn()->set_charset(Core::app()->cfg('db/encoding', 'utf8'))) {
            throw new DbError('Cannot change encoding.');
        }

        Profiler::addLog(__CLASS__ . ' connected');
    }

    protected $_last_debug_src = '';

    /**
     * @param        $sql
     * @param string $skip
     * @param bool $assoc
     * @return array|bool|Record
     * @throws DbError
     */
    public function query($sql, $skip = '', $assoc = TRUE)
    {
        Profiler::addLog('SQL QUERY: <kbd title="' . $sql . '">' . $sql . '</kbd>', Profiler::LEVEL_SQL);
        $benchmark = Profiler::benchStart(\TProfiler::BENCH_CAT_SQL, 'SQL QUERY: <kbd title="' . $sql . '">' . $sql . '</kbd>');

        $this->_queries++;
        $this->_queryList[] = $sql;

        if (Core::app()->getMode() != Core::MODE_PROD or Core::app()->cfg('db/debug', false) == true) {
            // add info about this query and it's location
            if (Core::app()->hasLoadedModule('toolbar')) {
                $b = array_reverse(debug_backtrace(~DEBUG_BACKTRACE_PROVIDE_OBJECT));

                $db = array();

                foreach ($b as $_b) {
                    if (empty($_b['file'])) {
                        $db[] = ' > ' . $_b['class'] . '::' . $_b['function'];
                    } else {
                        // eliminate /core/tikori, but keep core modules/views
                        if (preg_match('#(?:\\\\|\/)(app|modules|view|theme)(?:\\\\|\/)#', $_b['file'])) {
                            $db[] = ' > ' . (!empty($_b['file']) ? $_b['file'] : '--') . ':' . (!empty($_b['line']) ? $_b['line'] : '0');
                        }
                    }
                }

                $str = '';
                // dont add path if it's same line as previously - usually it's a eager/lazy load
                if (count($db) > 0 and $db[count($db) - 1] != $this->_last_debug_src) {
                    $str = '<div style="border-top: 1px solid black; margin: 10px 0;"></div><tt>' . implode('<br/>', $db) . '</tt>';
                    $this->_last_debug_src = $db[count($db) - 1];
                }

                $str .= sprintf('<code>%s</code>', $sql);

                Core::app()->toolbar->putValueToTab('SQL', $str);
                Core::app()->toolbar->setNotificationsNumberOnTab('SQL', $this->_queries);
                Core::app()->toolbar->addCounter('SQL Query');
            }
        }

        if (preg_match('/^(create|drop|insert|update|delete|replace|alter|set|truncate)/i', trim($sql))) {
            $result = $this->conn()->query($sql);
            Profiler::addLog('Exec finished');
            $time = Profiler::benchFinish($benchmark);
            if ($result === false) {
                $errNum = mysqli_errno($this->conn());
                throw new DbError($errNum . ': ' . $this->conn()->error . '<br/><br/><code>' . $sql . '</code>', $errNum);
            }
            return true;
        } else {
            $result = mysqli_query($this->conn(), $sql);
            Profiler::addLog('Query finished');
            $time = Profiler::benchFinish($benchmark);
        }

        if (Core::app()->hasLoadedModule('toolbar')) {
            Core::app()->toolbar->addTimer('SQL Query Time', $time);
        }


        if (!$result instanceof mysqli_result) {
//            if (mysqli_error($this->conn())) {
            throw new DbError('Expected mysql_result, got ' . (is_object($result) ? get_class($result) : gettype($result)) . '<br/>' . $sql . '<br/>' . $this->conn()->error, mysqli_errno($this->conn()));
//            }
//            return NULL;
        } else {
            if ($result instanceof mysqli_result) {
                $return = array();
                if ($result->num_rows) {
                    Profiler::addLog('Fetchich Mysql result to <kbd>Record Class</kbd>');
                    $benchmark = Profiler::benchStart(\TProfiler::BENCH_CAT_SQL_FETCH, 'SQL Fetch Time');

                    // fetch all rows as record
                    while ($row = $result->fetch_object('Record')) {
                        /* @var $row Record */
                        if ($assoc == false) {
                            $row->removeAssocKeys();
                        }
                        $return[] = $row;
                    }

                    Profiler::addLog('Fetch & records creating finished');
                    $time = Profiler::benchFinish($benchmark);

                    if (Core::app()->hasLoadedModule('toolbar')) {
                        Core::app()->toolbar->addTimer('SQL Fetch Time', $time);
                    }
                    return $return;
                } else {
                    return new Record();
                }
            } else {
                throw new DbError('SQL ERROR ' . $sql . '<br/>' . mysqli_error($this->conn()), mysqli_errno($this->conn()));
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
     * @return mysqli|PDO
     */
    public function conn()
    {
        return parent::conn();
    }

}
