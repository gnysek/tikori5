<?php

/**
 * Class RadController
 */

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

		$rulesHtml = array();

        foreach ($rules as $ruleName => $rule) {
            foreach ($rule as $ruleValue => $ruleFields) {
				$rulesHtml[] = 'array(array(' . implode(', ', $ruleFields) . '), \'' . $ruleName . '\', \'' . $ruleValue . '\')';
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
			'model' => $model,
			'modelName' => $modelName,
			'result' => $result,
			'primaryKey' => $primaryKey,
			'rulesHtml' => $rulesHtml,
			'relationsHtml' => $relationsHtml,
		)));

        if ($this->request->isPost() and $this->request->getPost('save')) {
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
                                    'displaySrc'        => $displaySrc,
                                    'fileExists' => $fileExists,
                                    'table'      => $model,
                                    'PK'         => $primaryKey,
                                    'file'       => implode(PHP_EOL, $buildFile),
                                    'relations'  => $relations,
                                    'fields'     => $fields,
					'relationsHtml' => $relationsHtml,
                               )
            );
        }
    }
}
