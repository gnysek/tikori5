<?php echo '<?php'; ?>

    /**
    <?php foreach (array() as $type => $name): ?>
    * @property
    <?php endforeach; ?>
    */
    class <?php echo ucfirst(strtolower($model)) ?> extends Model
    {
        ';
            protected $_primaryKey = \'' . $primaryKey . '\';';
        /*';
            public function getTable()';
            {';
                return \'' . $model . '\';';
            }';*/
        ';

            /**';
             * @param null|string $model';
             * @return ' . ucfirst(strtolower($model));
             */';
            public static function model($model = __CLASS__)';
            {';
                return parent::model($model);';
            }';
        ';

        /* getFields */
            public function getFields()';
            {';
                return array(';
        foreach ($result as $v) {
                        \'' . $v['Field'] . '\',';
        }
                );';
            }';
        ';

        /* rules */
            public function rules()';
            {';
                return array(';
        $rules = array();
        foreach ($result as $v) {
            if (preg_match('/char/', $v['Type'])) {
                $rules['maxlen'][preg_replace('/.*\(([0-9]*)\)/', '$1', $v['Type'])][] = '\'' . $v['Field'] . '\'';
            }

            if (preg_match('/int/', $v['Type'])) {
                $rules['int']['true'][] = '\'' . $v['Field'] . '\'';
            }

            if ($v['Null'] == 'NO' && $v['Extra'] != 'auto_increment') {
                $rules['required']['true'][] = '\'' . $v['Field'] . '\'';
            }
        }

        foreach ($rules as $ruleName => $rule) {
            foreach ($rule as $ruleValue => $ruleFields) {
                            array(array(' . implode(', ', $ruleFields) . '), \'' . $ruleName . '\', \''
                    . $ruleValue . '\'),';
            }
        }

        /* foreign keys */

        $foreign = Core::app()->db->query('SHOW CREATE TABLE ' . $model);

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
                    $table->columns[$name]->isForeignKey=true;*/
            }
        }

                );';
            }';
        ';

        /* additional relations */
        if ($this->request->isPost()) {
            if ($this->request->getPost('addRelations') == 1) {
                $relations = array();
                foreach ($this->request->getPost('relation') as $relation) {
                    $relations[] = $relation;
                }

                if (count($relations)) {
                        public function relations()';
                        {';
                            return array(';
                    foreach ($relations as $relation) {
                                    ' . $relation;
                    }
                            );';
                        }';
                    ';
                }
            }
        }

        }';
        ';
