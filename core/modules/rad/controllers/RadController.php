<?php

class RadController extends Controller
{

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

        $src = TIKORI_ROOT . '/app/models/' . ucfirst($model) . '.php';
        $fileExists = file_exists($src);

        $primaryKey = '';
        foreach ($result as $v) {
            if ($v['Key'] == 'PRI') {
                #var_dump($v);
                $primaryKey = $v['Field'];
                break;
            }
        }

        $buildFile = array('<?php');
        $buildFile[] = '/** Class ' . ucfirst(strtolower($model));
        $buildFile[] = ' *';
        foreach ($result as $v) {
            $buildFile[] = ' * @property ' . (preg_match('/int/', $v['Type']) ? 'int' : 'string') . ' $' . $v['Field'];
        }
        $buildFile[] = ' */';
        $buildFile[] = 'class ' . ucfirst(strtolower($model)) . ' extends Model {';
        $buildFile[] = '';
        $buildFile[] = '    protected $_primaryKey = \'' . $primaryKey . '\';';
        /*$buildFile[] = '';
        $buildFile[] = '    public function getTable()';
        $buildFile[] = '    {';
        $buildFile[] = '        return \'' . $model . '\';';
        $buildFile[] = '    }';*/
        $buildFile[] = '';

        $buildFile[] = '    /**';
        $buildFile[] = '     * @param null|string $model';
        $buildFile[] = '     * @return ' . ucfirst(strtolower($model)) . '|Model';
        $buildFile[] = '     */';
        $buildFile[] = '    public static function model($model = __CLASS__)';
        $buildFile[] = '    {';
        $buildFile[] = '        return parent::model($model);';
        $buildFile[] = '    }';
        $buildFile[] = '';

        /* getFields */
        $buildFile[] = '    public function getFields()';
        $buildFile[] = '    {';
        $buildFile[] = '        return array(';
        foreach ($result as $v) {
            $buildFile[] = '            \'' . $v['Field'] . '\',';
        }
        $buildFile[] = '        );';
        $buildFile[] = '    }';
        $buildFile[] = '';

        /* rules */
        $buildFile[] = '    public function rules()';
        $buildFile[] = '    {';
        $buildFile[] = '        return array(';
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
                $buildFile[] = '            array(array(' . implode(', ', $ruleFields) . '), \'' . $ruleName . '\', \''
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

        $buildFile[] = '        );';
        $buildFile[] = '    }';
        $buildFile[] = '';

        /* additional relations */
        if ($this->request->isPost()) {
            if ($this->request->getPost('addRelations') == 1) {
                $relations = array();
                foreach ($this->request->getPost('relation') as $relation) {
                    $relations[] = $relation;
                }

                if (count($relations)) {
                    $buildFile[] = '    public function relations()';
                    $buildFile[] = '    {';
                    $buildFile[] = '        return array(';
                    foreach ($relations as $relation) {
                        $buildFile[] = '            ' . $relation;
                    }
                    $buildFile[] = '        );';
                    $buildFile[] = '    }';
                    $buildFile[] = '';
                }
            }
        }

        $buildFile[] = '}';
        $buildFile[] = '';

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

        if ($this->request->isPost()) {
            $dir = dirname($src);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($src, implode(PHP_EOL, $buildFile));
            $this->redirect('rad/model');
        } else {
            $this->render(
                'modelCreate', array(
                                    'src'        => $src,
                                    'fileExists' => $fileExists,
                                    'table'      => $model,
                                    'PK'         => $primaryKey,
                                    'file'       => implode(PHP_EOL, $buildFile),
                                    'relations'  => $relations,
                                    'fields'     => $fields,
                               )
            );
        }
    }
}
