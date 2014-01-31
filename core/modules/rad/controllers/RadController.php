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

        $primaryKey = '';
        foreach ($result as $v) {
            if ($v['Key'] == 'PRI') {
                #var_dump($v);
                $primaryKey = $v['Field'];
                break;
            }
        }

        $buildFile = array('<?php');
        $buildFile[] = 'class ' . ucfirst(strtolower($model)) . ' extends Model {';
        $buildFile[] = '';
        $buildFile[] = '    protected $_primaryKey = \'' . $primaryKey . '\';';
        /*$buildFile[] = '';
        $buildFile[] = '    public function getTable()';
        $buildFile[] = '    {';
        $buildFile[] = '        return \'' . $model . '\';';
        $buildFile[] = '    }';*/
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
                $buildFile[] = '            array(' . implode(',', $ruleFields) . ', \'' . $ruleName . '\', \''
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

        $buildFile[] = '}';

        $this->render('modelCreate', array('table' => $model, 'file' => implode(PHP_EOL, $buildFile)));
    }
}
