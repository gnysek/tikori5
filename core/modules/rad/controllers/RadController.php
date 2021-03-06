<?php

/**
 * Class RadController
 */

class RadController extends Controller
{
    const EVENT_RAD_BEFORE_ACTION = 'rad_before_action';

    protected function _actionBefore()
    {
        Core::app()->mode = Core::MODE_PROD;
        $canProceed = Core::app()->cfg('rad/allow-all', false);
        Core::app()->observer->fireEvent(self::EVENT_RAD_BEFORE_ACTION, ['result' => &$canProceed]);
        if ($canProceed) {
            return parent::_actionBefore();
        } else {
            return false;
        }
    }

    public function indexAction()
    {
        $this->render('index');
    }

    public function modelAction()
    {
        $result = Core::app()->db->query('SHOW tables', '', false);

        $this->render('model', array('tables' => $result));
    }

    public function modelCreateAction($model, $save = '')
    {
        $result = Core::app()->db->query('SHOW columns in ' . $model);
        $modelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $model)));
        $displaySrc = '/app/models/' . $modelName . '.php';
        $src = TIKORI_ROOT . $displaySrc;
        $fileExists = file_exists($src);


        $primaryKey = '';
        foreach ($result as $v) {
            if ($v['Key'] == 'PRI') {
                #var_dump($v);
                $primaryKey = $v['Field'];
                break;
            }
        }

        $rules = array();
        foreach ($result as $v) {
            if ($v['Field'] == $primaryKey) {
                continue;
            }

            if (preg_match('/char/', $v['Type'])) {
                $rules['maxlen'][preg_replace('/.*\(([0-9]*)\)/', '$1', $v['Type'])][] = 'self::FIELD_' . strtoupper($v['Field']);
            }

            if (preg_match('/int/', $v['Type'])) {
                $rules['int']['true'][] = 'self::FIELD_' . strtoupper($v['Field']);
            }

            if ($v['Null'] == 'NO' && $v['Extra'] != 'auto_increment') {
                $rules['required']['true'][] = 'self::FIELD_' . strtoupper($v['Field']);
            }
        }

        $rulesHtml = array();

        foreach ($rules as $ruleName => $rule) {
            foreach ($rule as $ruleValue => $ruleFields) {
                if ($ruleValue !== 'true') {
                    $ruleValue = '\'' . $ruleValue . '\'';
                }
                #$constFields = $ruleFields;
                #array_walk($constFields, function(&$row, &$key) { $row = 'self::FIELD_' . trim(strtoupper($row), '\'');});
                $rulesHtml[] = '[[' . implode(', ', $ruleFields) . '], \'' . $ruleName . '\', ' . $ruleValue . ']';
            }
        }

        /* foreign keys */

        /*$foreign = Core::app()->db->query('SHOW CREATE TABLE ' . $model);

        $matches = array();
        $regexp = '/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';
        foreach ($foreign as $sql) {
            if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
                break;
            }
        }
        $foreignKeys = array();
        foreach ($matches as $match) {
            $keys = array_map('trim', explode(',', str_replace('`', '', $match[1])));
            $fks = array_map('trim', explode(',', str_replace('`', '', $match[3])));
            foreach ($keys as $k => $name) {
                $foreignKeys[$name] = array(str_replace('`', '', $match[2]), $fks[$k]);
                /*if(isset($table->columns[$name]))
                    $table->columns[$name]->isForeignKey=true;* /
            }
        }

        $buildFile[] = '        );';
        $buildFile[] = '    }';
        $buildFile[] = '';
		*/

        /* additional relations */
        $relationsHtml = array();
        if ($this->request->isPost()) {
            if ($this->request->getPost('addRelations') == 1) {
                $relations = array();
                if ($this->request->getPost('relation')) {
                    foreach ($this->request->getPost('relation') as $relation) {
                        $relations[] = $relation;
                    }
                }

                if (count($relations)) {
                    foreach ($relations as $relation) {
                        $relationsHtml[] = $relation;
                    }
                }
            }
        }

        if ($fileExists) {
            $class = new $modelName;
            /* @var $class TModel */
            foreach ($class->relations() as $relationName => $relation) {
                switch ($relation[0]) {
                    case TModel::HAS_MANY:
                        $relationsHtml[] = '\'' . $relationName . '\' => array(self::HAS_MANY, \'' . $relation[1] . '\', \'' . $relation[2] . '\'' . (!empty($relation[3]) ? (', \'' . $relation[3] . '\'') : '') . ')';
                        break;
                    case TModel::BELONGS_TO:
                        $relationsHtml[] = '\'' . $relationName . '\' => array(self::BELONGS_TO, \'' . $relation[1] . '\', \'' . $relation[2] . '\'' . (!empty($relation[3]) ? (', \'' . $relation[3] . '\'') : '') . ')';
                }
            }
        }

        $relationsHtml = array_unique($relationsHtml);

        # --- get additional tables for relations ---
        $relationsTables = Core::app()->db->query('SHOW tables', '', false);
        $relations = array();
        $fields = array();
        foreach ($relationsTables as $table) {
            $relationInfo = Core::app()->db->query('SHOW columns in ' . $table[0]);
            foreach ($relationInfo as $relationData) {
                $relations[$table[0]][$relationData['Field']] = ($relationData['Key'] == 'PRI') ? true : false;
            }
        }

        foreach ($result as $column) {
            $fields[] = $column['Field'];
        }

        $buildFile = array($this->renderPartial('modelTemplate', array(
            'model'         => $model,
            'modelName'     => $modelName,
            'result'        => $result,
            'primaryKey'    => $primaryKey,
            'rulesHtml'     => $rulesHtml,
            'relationsHtml' => $relationsHtml,
        )));

        if ($this->request->isPost() and $this->request->getPost('save')) {
            $dir = dirname($src);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($src, implode(PHP_EOL, $buildFile));

            Core::app()->db->getSchema()->clearSchemaCache();

            $this->redirect('rad/model');
        } else {
            $this->render(
                'modelCreate',
                array(
                    'src'           => $src,
                    'displaySrc'    => $displaySrc,
                    'fileExists'    => $fileExists,
                    'table'         => $model,
                    'PK'            => $primaryKey,
                    'file'          => implode(PHP_EOL, $buildFile),
                    'relations'     => $relations,
                    'fields'        => $fields,
                    'relationsHtml' => $relationsHtml,
                )
            );
        }
    }

    public function syncAction()
    {
        error_reporting(E_ALL);
        Core::app()->mode = Core::MODE_DEV;

        $result = Core::app()->db->query('SHOW tables', '', false);

        $tables = [];
        foreach ($result as $row) {
            $tables[$row[0]] = [
                'json' => false,
                'new'  => false,
            ];

            $schemaFile = TIKORI_ROOT . '/app/config/db/' . $row[0] . '.json';

            if (file_exists($schemaFile)) {
                $tables[$row[0]]['json'] = true;
            }
        }


        $files = glob(TIKORI_ROOT . '/app/config/db/*.json');

        // search for new
        foreach ($files as $file) {
            $tableName = basename($file, '.json');
            if (!array_key_exists($tableName, $tables)) {
                $tables[$tableName] = ['json' => true, 'new' => true];
            }
        }


        $this->render('sync-index', array('result' => $result, 'tables' => $tables));
    }

    public function syncModelAction($model)
    {
        // todo: securize
        var_dump($model);

        $file = TIKORI_ROOT . '/app/config/db/' . $model . '.json';
        $tableName = $model;

        try {
            $schema = Core::app()->db->getSchema()->getTableSchema($tableName, true);
            $json = json_decode(file_get_contents($file), true);

//                var_dump($schema);
//                var_dump($json);

            $fileData = [];
            $dbData = [];

            $columnBefore = null;
            foreach ($json['structure'] as $columnName => $columnData) {
                $fileData[$columnName] = [
                    't'       => Core::app()->db->getSchema()->getColumnSimplifiedType($columnData['t']),
                    's'       => $columnData['s'],
                    '_after'  => empty($columnBefore) ? 'FIRST' : ('AFTER ' . $columnBefore),
                    '_before' => $columnBefore,
                ];
                $columnBefore = $columnName;
            }

            $columnBefore = null;
            foreach ($schema->columns as $columnName => $columnData) {
                $dbData[$columnName] = [
                    't'       => $columnData->type,
                    's'       => $columnData->size,
                    '_after'  => empty($columnBefore) ? 'FIRST' : ('AFTER ' . $columnBefore),
                    '_before' => $columnBefore,
                ];
                $columnBefore = $columnName;
            }

//            var_dump($fileData, $dbData);

            $fieldsToAdd = array_diff(array_keys($fileData), array_keys($dbData));
            $fieldsToDelete = array_diff(array_keys($dbData), array_keys($fileData));

//            var_dump($fieldsToAdd, $fieldsToDelete);

            // delete fields
            foreach ($fieldsToDelete as $col) {
                $sql = 'ALTER TABLE ' . $tableName . ' DROP COLUMN `' . $col . '`;';
                var_dump($sql);
                unset($dbData[$col]); // delete, as we gonna use it as reference later
            }

            // add new fields
            foreach ($fieldsToAdd as $col) {
                $sql = 'ALTER TABLE ' . $tableName;
                $sql .= ' ADD column `' . $this->_getColumnStructure($col, $fileData[$col]) . '` ' . $fileData[$col]['_after'];
                $sql .= ';';

                //$dbData[$col][] = $fileData[$col]; // todo - insert in middle too!!!
                // maybe this works ?
                $this->_addArrayElementAfter($dbData, $col, $fileData[$col], trim(str_replace(['AFTER ', '`'], '', $fileData[$col]['_after'])));
            }

            // reorder tables
            $currentFields = array_keys($dbData);
            $newFields = array_keys($fileData);

            var_dump('db', $currentFields, 'new', $newFields);

            $before = null;
            foreach ($newFields as $pos => $columnName) {
                if ($columnName != $currentFields[$pos]) {
                    $sql = 'ALTER TABLE `' . $tableName . '` MODIFY COLUMN ' . $this->_getColumnStructure($columnName, $dbData[$columnName]) . ' AFTER `' . $before . '`;';

                    var_dump($currentFields, array_search($columnName, $currentFields));
                    $this->_moveArrayElement($currentFields, array_search($columnName, $currentFields), $pos);
                    var_dump($sql);
                    var_dump($currentFields);
                }

                $before = $newFields[$pos];
            }

            //$this->redirect('rad/sync');
            return;
        } catch (\Exception $e) {
            // not exists, create
            if (file_exists($file)) {

                $_fields = [];
                $json = json_decode(file_get_contents($file), true);

                foreach ($json['structure'] as $column => $data) {
                    $_fields[$column] = $this->_getColumnStructure($column, $data);
                }

                $sql = 'CREATE TABLE `' . $tableName . '` (' . PHP_EOL;
                $sql .= implode(',' . PHP_EOL, $_fields);
                $sql .= PHP_EOL . ') ENGINE=InnoDB DEFAULT CHARSET=utf8;';

//            var_dump($sql);

                Core::app()->db->update($sql);

                if (isset($json['meta']['pk'])) {
                    $sql = 'ALTER TABLE `' . $tableName . '` ADD PRIMARY KEY (`' . $json['meta']['pk'] . '`);';
//                var_dump($sql);

                    Core::app()->db->update($sql);

                    if (isset($json['meta']['ai']) and $json['meta']['ai'] == $json['meta']['pk']) {
                        $sql = 'ALTER TABLE `' . $tableName . '` MODIFY ' . $_fields[$json['meta']['ai']] . ' AUTO_INCREMENT;';
//                    var_dump($sql);

                        Core::app()->db->update($sql);
                    }
                }
            }
        }

        $this->redirect('rad/sync');
    }

    public function dumpAllAction()
    {
        ob_start();
        $result = Core::app()->db->query('SHOW tables', '', false);

        foreach ($result as $row) {
            $exists = true;
            try {
                $modelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $row[0])));
                $class = new $modelName;

                echo '<h1>' . $modelName . '</h1>';
                echo '<div style="max-height: 150px; overflow-y: auto">';
                $this->_dumpModelAction($row[0]);
                echo '</div>';
            } catch (Exception $e) {
                $exists = false;
            }
        }
        $html = ob_get_clean();

        $this->render(null, $html);
    }

    public function compareAllAction()
    {
        $result = Core::app()->db->query('SHOW tables', '', false);

        echo '<h1>Differences list</h1>';

        $anyDiff = false;
        foreach ($result as $row) {
            $exists = true;
            try {
                $modelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $row[0])));
                $class = new $modelName;
            } catch (Exception $e) {
                $exists = false;
            }

            if ($exists) {
                $database = $this->_dumpModelAction($row[0], false);
                $disk = file_exists(TIKORI_ROOT . '/app/config/db/' . $row[0] . '.json') ? file_get_contents(TIKORI_ROOT . '/app/config/db/' . $row[0] . '.json') : '';

                $diff = RadDiff::compare($database, $disk);
                $isDifference = false;
                foreach ($diff as $diffFile) {
                    if ($diffFile[1] != 0) {
                        $isDifference = true;
                        $anyDiff = true;
                        break;
                    }
                }

                if ($isDifference) {
                    echo '<h3>' . $modelName . '</h3>';
                    echo RadDiff::toTable($diff, '', '');
                }
            }
        }

        if (!$anyDiff) {
            echo 'No diff found';
        }

        $html = ob_get_clean();

        $this->render('compare-all', ['content' => $html]);
    }

    public function dumpModelAction($model)
    {
        $this->_dumpModelAction($model);
    }

    protected function _dumpModelAction($model, $saveOnDisk = true)
    {
        // todo: securize
        #var_dump($model);

        $file = TIKORI_ROOT . '/app/config/db/' . $model . '.json';
        $tableName = $model;

        $schema = Core::app()->db->getSchema()->getTableSchema($tableName, true);

        $indexes = Core::app()->db->query("SHOW INDEXES FROM $tableName");
        $tableIndexes = [];

        foreach ($indexes as $index) {
            #var_dump($index);

            if (!array_key_exists($index['Key_name'], $tableIndexes)) {
                $tableIndexes[$index['Key_name']] = [
                    'name'   => $index['Key_name'],
                    'fields' => [],
                    'unique' => $index['Non_unique'] == 0 ? true : false,
                ];
            }

            $tableIndexes[$index['Key_name']]['fields'][] = $index['Column_name'];
        }

        #var_dump($tableIndexes);

        $dbData = ['structure' => [], 'meta' => []];
        foreach ($schema->columns as $columnName => $columnData) {

            $type = $columnData->type;
            switch ($columnData->type) {
                case 'integer':
                    $type = 'int';
                    break;
                case 'varchar':
                    $type = 'string';
                    break;
            }

            $dbData['structure'][$columnName] = [
                't' => $type,
                's' => $columnData->size,
                'n' => $columnData->allowNull ? true : false,
                'd' => $columnData->defaultValue,
            ];

            if ($columnData->isPrimaryKey) {
                $dbData['meta']['pk'] = $columnName;
            }
            if ($columnData->autoIncrement) {
                $dbData['meta']['ai'] = $columnName;
            }
        }

        $dbData['indexes'] = $tableIndexes;

        if ($saveOnDisk) {
            file_put_contents($file, json_encode($dbData, JSON_PRETTY_PRINT));
            if ($this->action == 'dumpModel') {
                $this->render(null, '<pre>' . json_encode($dbData, JSON_PRETTY_PRINT) . '</pre>');
            } else {
                echo '<pre>' . json_encode($dbData, JSON_PRETTY_PRINT) . '</pre>';
            }
        } else {
            return json_encode($dbData, JSON_PRETTY_PRINT);
        }
    }

    /*
     *
     * ---------------------------------------------------------------------------------
     *
     */

    protected function _getColumnStructure($column, $data)
    {
        $row = '`' . $column . '` ';

        switch ($data['t']) {
            case 'string':
                $row .= 'varchar(' . $data['s'] . ')';
                break;
            case 'int':
                $row .= 'int(' . $data['s'] . ')';
                break;
        }

        $default = isset($data['d']) ? $data['d'] : null;

        if (isset($data['n']) and $data['n'] === true) {
            $row .= ' NULL';

            if (!is_array($default)) {
                $row .= ' DEFAULT ' . ($default === null ? 'NULL' : '\'' . $default . '\'');
            }
        } else {
            $row .= ' NOT NULL';

            if (!is_array($default)) {
                // fix if default set to null, cause it cannot be
                $row .= ' DEFAULT ' . ($default === null ? 0 : '\'' . $default . '\'');
            }
        }

        return $row;
    }

    protected function _moveArrayElement(&$array, $a, $b)
    {
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
    }

    protected function _addArrayElementAfter(&$array, $key, $val, $after)
    {
        $_pos = -1;

        if ($after !== null) {
            $_pos = array_search($after, array_keys($array));

            if ($_pos === false) {
                $_pos = count($array);
            }
        }

        $_bef = array_slice($array, 0, $_pos + 1);
        $_aft = array_slice($array, $_pos + 1);

        $array = $_bef + [$key => $val] + $_aft;
    }

}
