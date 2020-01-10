<?php

/**
 * Class SingletonOptions
 * this class keeps key-value values for displaying options for AR Models or <select> elems in HTML
 */
class SingletonOptions
{

    protected static $_loaded = array();

    /**
     * @param      $modelClassName   Model class name
     * @param      $key     key columns, for <option value="key">
     * @param      $value   value column, for <option>value</option>
     * @param array $queryOptions
     * @param bool $rewrite whether rewrite if exists
     * @return array
     * @throws Exception
     */
    public static function create($modelClassName, $key, $value, $queryOptions = [], $rewrite = false)
    {
        $arr_key = $modelClassName . '_' . $key . '_' . $value;
        if (!array_key_exists($arr_key, self::$_loaded) or $rewrite === true) {
            $result = TModel::model($modelClassName)->findAll(-1, 0, array('order' => $key . ' ASC') + $queryOptions);
            $values = array();
            foreach ($result as $row) {
                $values[$row->$key] = $row->$value;
            }
            self::$_loaded[$arr_key] = $values;
        }

        return self::$_loaded[$arr_key];
    }

    /**
     * @param $modelClassName
     * @param $key
     * @param $value
     * @param array $queryOptions
     * @return array
     * @throws Exception
     */
    public static function get($modelClassName, $key, $value, $queryOptions = [])
    {
        return self::create($modelClassName, $key, $value, $queryOptions, false);
    }

    /**
     * @param $table
     * @param $key_column
     * @param $value_column
     * @param $values
     */
    public static function set($table, $key_column, $value_column, $values)
    {
        $arr_key = $table . '_' . $key_column . '_' . $value_column;
        self::$_loaded[$arr_key] = $values;
    }
}
