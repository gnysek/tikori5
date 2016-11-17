<?php

/**
 * Class SingletonOptions
 * this class keeps key-value values for displaying options for AR Models or <select> elems in HTML
 */
class SingletonOptions
{

    protected static $_loaded = array();

    /**
     * @param      $table   Model class name
     * @param      $key     key columns, for <option value="key">
     * @param      $value   value column, for <option>value</option>
     * @param bool $rewrite whether rewrite if exists
     * @return array
     */
    public static function create($table, $key, $value, $rewrite = false)
    {
        $arr_key = $table . '_' . $key . '_' . $value;
        if (!array_key_exists($arr_key, self::$_loaded) or $rewrite === true) {
            $result = TModel::model($table)->findAll();
            $values = array();
            foreach ($result as $row) {
                $values[$row->$key] = $row->$value;
            }
            self::$_loaded[$arr_key] = $values;
        }

        return self::$_loaded[$arr_key];
    }

    /**
     * @param $table
     * @param $key
     * @param $value
     * @return array
     */
    public static function get($table, $key, $value)
    {
        return self::create($table, $key, $value, false);
    }
}
