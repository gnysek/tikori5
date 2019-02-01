<?php

/**
 * Class Model
 *
 * @property string $tableName  Table Name
 * @property array $attributes Attribute values
 */
abstract class TModel implements IteratorAggregate, ArrayAccess
{

    const BELONGS_TO = 'BELONGS_TO';
    const HAS_ONE = 'HAS_ONE';
    const HAS_MANY = 'HAS_MANY';
    const STATS = 'STATS';

//	const MANY_MANY = 4;

    protected $_table = '';
    protected $_fields = array();
    protected $_values = array();
    protected $_original = array();
    protected $_rules = array();
    protected $_related = array();
    protected static $_oop_relations = array();
    protected $_primaryKey = 'id';
    protected $_scopes = array();
    protected $_relations = array();
    protected $_isNewRecord = true;
    /**
     * @var array List of attributes that should be hidden when displaying (e.g. password)
     */
    protected $_hidden = array(); //TODO make it working
    protected $_appends = array(); //TODO list of all getters that should be automatically added as fake attrs
    // TODO: allow to have more than one error per attribute
    protected $_errors = array();
    public $_modified = array();
    public $tableName = '';
    //public $_new = false;
    public $_eagers = array();
    /**
     * @var bool Should created_at and updated_at fields be updated automatically?
     */
    public $timestamps = true;
    public $massProtected = array(); // which attrs can be saved using mass
    /**
     * @var null|TDbTableSchema
     */
    protected $_schema = null;

    public function __construct($attributes = array(), $isNew = true)
    {
        $this->_schema = Core::app()->db->getTableInfo($this->getTable());
        $this->_scopes = $this->scopes();
        $this->_relations = $this->relations();
        $this->_fields = $this->getFields();
        $class = get_class($this);

        if (!array_key_exists($class, self::$_oop_relations)) {
            // always fill with at least empty array, so haveRelationInClass method will work
            self::$_oop_relations[$class] = array();
            if (!empty($this->_relations)) {
                foreach ($this->_relations as $relationName => $relation) {
                    self::$_oop_relations[$class][$relationName] = new TModelRelation($relationName, $relation[0], $class, $relation[1], $relation[2]);
                }
            }
        }

        $this->_table = $this->getTable();
        if (!is_array($this->_primaryKey)) {
            $this->_primaryKey = array($this->_primaryKey);
        }

        // set default values
        if (!empty($this->_fields)) {
            foreach ($this->_fields as $fieldName) {
                if (array_key_exists($fieldName, $this->_relations)) {
                    throw new DbError('Model ' . get_class($this) . ' have field named same as relation: ' . $fieldName);
                }
                $this->_values[$fieldName] = $this->_original[$fieldName] = ($fieldName == $this->getFirstPK()) ? null : ($this->_schema->getColumn($fieldName)->defaultValue);
            }
        }
        $this->_rules = $this->_prepareRules();
        $this->_isNewRecord = $isNew;

        $this->_populate($attributes);
        if ($this->_isNewRecord) {
            $this->_populateOnNewRecord();
        }
        //$this->_new = true;
    }

    public function getTable()
    {
        if (empty($this->_table)) {
            $this->_table = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", get_called_class()));
        }
        return $this->_table;
    }

    public static function getTableName($modelName)
    {
        return strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $modelName));
    }

    public function getPK()
    {
        return $this->_primaryKey;
    }

    public function getFirstPK() // TODO: fix all functions which may use this, so original getPK is better resolved in case of two PK
    {
        return $this->_primaryKey[0];
    }

    /**
     * @param string $model
     *
     * @return TModel
     */
    public static function model($model = null)
    {
        if ($model == null) {
            $model = get_called_class();
        }
        return new $model();
    }

    public function getValues()
    {
        return $this->_values;
    }

    public function rules()
    {
        return array();
    }

    private function _prepareRules()
    {
        // TODO: caching ?
        $rules = array();

        foreach ($this->rules() as $ruleDefinition) {
            // convert one item rule to array
            if (!is_array($ruleDefinition[0])) {
                $ruleDefinition[0] = array($ruleDefinition[0]);
            }

            $params = array_slice($ruleDefinition, 2);
            foreach ($ruleDefinition[0] as $field) {
                // create rule array if not yet exists
                if (!array_key_exists($field, $rules)) {
                    $rules[$field] = array();
                }

                $rules[$field]['rules'][] = $ruleDefinition[1];
                if (!empty($params)) {
                    $rules[$field]['params'][$ruleDefinition[1]] = $params;
                }
            }
        }

        return $rules;
    }

    /**
     * @param $id
     *
     * @return $this|TModel
     * @throws DbError
     */
    public function load($id)
    {
        return $this->find($id);
    }

    /*
     * return $model
     */

    public function find($id)
    {
        // TODO - this is a weird hack
        if (is_array($id)) {
            if (!array_key_exists($this->_primaryKey[0], $id)) {
                $id = array($this->_primaryKey[0] => $id);
            }
            $w = array();
            foreach ($this->_primaryKey as $pk) {
                $w[] = array($pk, 'IN', $id[$pk]);
            }
            $collection = $this->findWhere(array($w));
            //$sql = DbQuery::sql()->select()->from($this->_table)->where(array($w));
        } else {
            $w = array();
            foreach ($this->_primaryKey as $pk) {
                $w = array($pk, '=', $id);
            }

            $collection = $this->findWhere($w);
            //$sql = DbQuery::sql()->select()->from($this->_table)->where($w);
        }

        if ($collection && $collection->count() == 0) {
            return null;
        } elseif ($collection && $collection->count() == 1) {
            // todo: make it so it doesn't need to use collection - make applying eagers more universal
            // for now it's just a hack to copy values from collection to current model, as when ->load() we don't need to assign into new value

            $record = $collection->getFirst();

            $this->_isNewRecord = false;
            $this->_populate($record->getValues());

            foreach ($record->getAlreadyLoadedRelations() as $relName) {
                $this->populateRelation($relName, $record->$relName);
            }

            return $this;
        } else {
            throw new DbError('Returned more than 1 record - PK wrongly defined?');
        }
    }

    // duplicate of load in fact...
//	public function findByPK($value) {
//		return $this->findBy($this->_primaryKey, $value);
//	}

    /**
     * @param $key
     * @param $value
     * @param bool $onlyFirst
     * @return null|$this|TModel|Collection
     * @throws Exception
     */
    public function findBy($key, $value, $onlyFirst = false)
    {
        $results = $this->findWhere(array($key, '=', $value));
        return ($onlyFirst && count($results) >= 1) ? $results[0] : $results;
        // it's now made in DbQuery
//        if (!is_numeric($value)) {
//            $value = Core::app()->db->protect($value);
//        }
//        $sql = DbQuery::sql()->select()->from($this->_table)->where(array($key, '=', $value));
//        $result = $sql->execute();
//        $return = array();
//        foreach ($result as $row) {
//            /* @var $row Result */
//            $c = self::model(get_called_class());
//            $c->setValues($row->getIterator()->getArrayCopy());
//
//            if ($onlyFirst) {
//                return $c;
//            }
//
//            $return[] = $c;
//        }
//        return $return;
//		return $this;
    }

    /**
     * @param null $where
     * @param       $limit
     * @param int $offset
     * @param array $conditions
     * @return Collection|$this[]
     * @throws Exception
     */
    public function findWhere($where = null, $limit = -1, $offset = 0, $conditions = array())
    {
        Profiler::addLog('FIND WHERE STARTED');
        $sql = DbQuery::sql()->select()->from($this->_table);
        if (!empty($where)) {
            $sql->where($where);
        }

        $sql->limit($limit, $offset);

        if (!empty($conditions)) {
            $sql->conditions($conditions);
        }

        $this->_applyEagers($sql);

        $result = $sql->execute();
        $return = array();
        $relationCacheByPk = array();
        foreach ($result as $row) {
            /* @var $row Result */
            //$c = self::model();
            $mainClassName = get_called_class();
//            $c->setValues($row->getIterator()->getArrayCopy());
            $values = $row->getIterator()->getArrayCopy();
            $c = new $mainClassName($values, false);
            /* @var $c TModel */
//            $c->setAttributes($values);

            // after getting data, load those which cannot be joined in another query, to attach them later
            if (!empty($this->_eagers)) {
                foreach ($this->_eagers as $k => $relationName) {

                    if (stripos($relationName, '.') > 0) {
                        /*$classes = explode('.', $relationName);
                        $currentClass = get_class($this);

                        for ($i = 0; $i < count($classes) - 1; $i++) {
                            $relData = self::getRelationForClass($currentClass, $classes[$i]);
                            var_dump($relData);
                            $currentClass = ucfirst($relData->class);
                            //self::haveRelationForClass($classes[$i]);
                            //var_dump($classes[$i]);
                            //$relationData = self::$_oop_relations[$currentClass][$classes[$i]];
                        }

                        if ($currentClass) {
                            var_dump($currentClass, $relationName, self::$_oop_relations[$currentClass][$classes[$i]], '----');
                        }*/
                        continue;
                    }

                    switch ($this->_relations[$relationName][0]) {
                        case self::BELONGS_TO:

                            $_cacheUniqueName = $relationName . $values[$this->_relations[$relationName][2]];

                            if (!array_key_exists($_cacheUniqueName, $relationCacheByPk)) {
//                                $r = Model::model($this->_relations[$relationName][1]);
                                $rvalues = array();

                                if ($schema = Core::app()->db->getSchema()->getTableSchema(self::getTableName($this->_relations[$relationName][1]))) {
                                    $fields = $schema->getColumnNames();
                                } else {
                                    throw new Exception('Cannot find Schema for table ' . self::getTableName($this->_relations[$relationName][1]));
                                }

                                foreach ($fields as $relationfield) {
                                    $relationfieldName = 'r' . ($k + 1) . '_' . $relationfield;
                                    $rvalues[$relationfield] = $values[$relationfieldName];
                                }
                                $relatedClassName = $this->_relations[$relationName][1];
                                $r = new $relatedClassName($rvalues, false);
                                /* @var $r TModel */
//                                $r->setAttributes($rvalues);
                                $relationCacheByPk[$_cacheUniqueName] = $r;
                            }

                            $c->populateRelation($relationName, $relationCacheByPk[$_cacheUniqueName]);
                            break;
                        default:
                            $methodName = '__doRelationBeforePopulate_' . $this->_relations[$relationName][0];
                            if (method_exists($this, $methodName)) {
                                $this->$methodName($relationName);
                            }
                            break;
                    }
                }
            }

            $return[] = $c;
        }

        // create collection and attach
        $collection = new Collection($return);

        if (!empty($this->_eagers)) {
            foreach ($this->_eagers as $relationName) {

                if (stripos($relationName, '.') > 0) {
                    continue;
                }

                $values = array();
                switch ($this->_relations[$relationName][0]) {
                    case self::HAS_MANY:
//                        var_dump($this->getPK());
//                        var_dump($collection->getColumnValues($this->getPK()));
                        $byField = $this->getFirstPK();
                        Profiler::addLog('FIND WHERE - getting related');
                        $related = $this->_getRelated($relationName, false, $collection->getColumnValues($byField));
                        Profiler::addLog('FIND WHERE - related got, will assign ' . count($related) . ' to ' . count($return) . ' records');
                        /* @var Collection $related */
//                        var_dump(count($related));
                        foreach ($return as $row) {
//                            var_dump($byField . ' ' . $row->$byField . ' ' . $this->_relations[$relationName][2]);
                            $toAssign = $related->getRowsByColumnValue($this->_relations[$relationName][2], $row->$byField);
//                            var_dump(count($toAssign));
                            /* @var TModel $row */
                            $row->populateRelation($relationName, $toAssign);
                        }
                        Profiler::addLog('FIND WHERE - related populated');
                        break;
                    case self::BELONGS_TO:
                        break;
                    /*case self::BELONGS_TO:
//                        var_dump($this->_relations[$relationName][2]);
                        $byField = $this->_relations[$relationName][2];
//                        var_dump($collection->getColumnValues($byField));
                        $related = $this->_getRelated($relationName, false, $collection->getColumnValues($byField));
                        $rel = Model::model($this->_relations[$relationName][1])->getPK();
//                        var_dump($related);
                        foreach ($return as $row) {
//                            var_dump($rel . ' ' . $byField . ' ' . $row->$byField);
                            $toAssign = $related->getRowsByColumnValue($rel, $row->$byField);
//                            var_dump(count($toAssign));
                            $row->populateRelation($relationName, $toAssign->getFirst());
                        }
//                        throw new Exception('Not yet implemented');
                        break;*/
                    default:
                        $methodName = '__doRelation_' . $this->_relations[$relationName][0];
                        if (method_exists($this, $methodName)) {
                            $this->$methodName($relationName);
                        } else {
                            throw new Exception('Not yet implemented, or no __doRelation_' . $methodName . ' found');
                        }
                        break;
                }
                ///var_dump($this->_getRelated($relationName, array('1')));
            }
        }

        Profiler::addLog('FIND WHERE apply dot eagers');

        $this->_applyDotEagersAfterLoadedNormalOnes($collection);

        Profiler::addLog('FIND WHERE FINISHED');
        return $collection;
    }

    /**
     * @param       $limit
     * @param int $offset
     * @param array $conditions
     * @return Collection|$this|Collection[]|$this[]
     * @throws Exception
     */
    public function findAll($limit = -1, $offset = 0, $conditions = array())
    {
        return $this->findWhere(null, $limit, $offset, $conditions);
    }

    public function count($by = null)
    {
        $sql = DbQuery::sql()->select('COUNT(*) AS tikori_total')->from($this->_table);
        $this->_applyEagers($sql);
        $result = $sql->execute();
        return (!empty($result[0])) ? $result[0]->tikori_total : 0;
    }

    /**
     * @param $by
     * @param $conditions
     *
     * @return int
     */
    public function countWhere($by = null, $conditions)
    {
        //TODO: with conditions not working here!
        $sql = DbQuery::sql()->select('COUNT(*) AS tikori_total')->from($this->_table);
        $sql->conditions($conditions);
        $this->_applyEagers($sql);
        $result = $sql->execute();
        return (!empty($result[0])) ? (int)$result[0]->tikori_total : 0;
    }

    protected function _applyEagers(DbQuery $sql)
    {
        if (!empty($this->_eagers)) {
            foreach ($this->_eagers as $k => $relationName) {

                if (stripos($relationName, '.') > 0) {
                    continue;
                }

                switch ($this->_relations[$relationName][0]) {
                    case self::BELONGS_TO:

                        $model = TModel::model($this->_relations[$relationName][1]);
                        /* @var $model TModel */
                        $sql->joinOn(array($k + 1, $model->getTable()), array($model->getFirstPK(), '=', $this->_relations[$relationName][2]));

                        break;
                    /*case self::HAS_MANY:

                        $model = Model::model($this->_relations[$relationName][1]);
                        $sql->joinOn($model->getTable(), array($this->_relations[$relationName][2], '=', $this->getPK()));
                    */
                    default:
                        $methodName = '__doEager_' . $this->_relations[$relationName][0];
                        if (method_exists($this, $methodName)) {
                            $this->$methodName($relationName, $sql);
                        }
                        //throw new DbError('Eager join for this type of relation is not yet implemented!');
                        break;
                }
            }
        }
    }

    protected function _applyDotEagersAfterLoadedNormalOnes($collection)
    {
        $subqueries = array();
        foreach ($this->_eagers as $relation) {
            if (stripos($relation, '.') > 0) {
                $classes = explode('.', $relation);
                $s = &$subqueries;
                foreach ($classes as $class) {
                    if (!array_key_exists($class, $s)) {
                        $s[$class] = array();
                    }

                    $s = &$s[$class];
                }
                unset($s);
            }
        }


        foreach ($subqueries as $subRelName => $subRelations) {

            //$ids = array();

            $relationDepthString = $subRelName;

            $_relData = self::$_oop_relations[get_class($this)][$subRelName];
            /* @var $_relData TModelRelation */

            /*switch ($_relData->relationType) {
                case self::BELONGS_TO:
                    $ids = $collection->getColumnValues($_relData->byField);
                    //var_dump($_relData, $ids);

                    $model = $this::model($_relData->class);
                    $result = static::model($_relData->class)->findWhere(array($model->getPK(), 'in', $ids));

                    foreach($collection as $row){
                        /* @var $row TModel * /
                        $row->populateRelation($_relData->relationName, $result->getRowsByColumnValue($model->getPK(), $row->{$_relData->byField})->getFirst());
                    }

                    if (Core::app()->hasLoadedModule('toolbar')) {
                        Core::app()->toolbar->putValueToTab('SQL', '---populated---' . implode(',', $this->_eagers));
                    }
                    break;
                default:
                    var_dump('not yet implemented sub-eager for ' . $_relData->relationType);
            }*/
            //var_dump($ids, self::$_oop_relations[get_class($this)][$subRelName]);

            #var_dump('', 'szukam dla : ' . $subRelName);
            #var_dump('', 'current relation finding stage : ' . $relationDepthString . '.....' );
            $this->__applyEagerForSpecificSubRelation($_relData, $subRelations, $relationDepthString, $collection);
            Profiler::addLog('Subrelation ' . $subRelName);
        }
    }

    protected function __applyEagerForSpecificSubRelation($parentRelationData, $curRelArray, $relationDepthString, $collection)
    {
        /* @var $parentRelationData TModelRelation */
        /* @var $currentRelation TModelRelation */
        $class = ucfirst($parentRelationData->class);
        #var_dump($parentRelationData->relationName, get_class($this), $class);
        foreach ($curRelArray as $subRelName => $subRelations) {
            #var_dump('--------------------------' . $relationDepthString);
            #var_dump($relationDepthString, $subRelName, $subRelations);

            $currentRelation = self::$_oop_relations[$class][$subRelName];

            #var_dump('', 'searching for: ' . $relationDepthString . '...' .$subRelName);

            #var_dump($parentRelationData, 'połączonych: ' . $subRelName, $currentRelation);

            switch ($currentRelation->relationType) {
                case self::HAS_MANY:
                case self::BELONGS_TO:

                    $values = array();

                    $innerGet = explode('.', $relationDepthString);
                    #var_dump($innerGet);

                    foreach ($collection as $row) {
                        #var_dump($row->title);

                        //$_related = $row->{$parentRelationData->relationName};
                        $_related = $row;
                        foreach ($innerGet as $deeperRelationName) {
                            $_related = $_related->{$deeperRelationName};
                        }
                        //$_related = $row->{$relationDepthString};

                        if (is_array($_related) or $_related instanceof Collection) {
                            foreach ($_related as $_rr) {
                                if ($_rr->{$_rr->getFirstPK()}) { //related->relationName
                                    //$values->$related
                                    if ($currentRelation->relationType == self::BELONGS_TO) {
                                        $values[] = $_rr->{$currentRelation->byField};
                                    } else {
                                        $values[] = $_rr->{$_rr->getFirstPK()};
                                    }

                                    #var_dump(true, $_related->{$_related->getPK()});
                                }
                            }
                        } else {
                            if ($_related->{$_related->getFirstPK()}) { //related->relationName
                                //$values->$related
                                if ($currentRelation->relationType == self::BELONGS_TO) {
                                    $values[] = $_related->{$currentRelation->byField};
                                } else {
                                    $values[] = $_related->{$_related->getFirstPK()};
                                }
                                #var_dump(true, $_related->{$_related->getPK()});
                            }
                        }
                        //var_dump($row->{$relData->relationName});
                    }

                    $values = array_unique($values);

                    #var_dump('', $deeperRelationName . '.' . $subRelName, $values);

                    if (count($values)) {

                        #var_dump(count($values));

                        $_model = $this->model($currentRelation->class);
                        $_byFields = $currentRelation->relationType == self::BELONGS_TO ? $_model->getFirstPK() : $currentRelation->byField;
                        ToolbarModule::debug($values);

                        if (is_array($values)) {
                            sort($values);
                        }

                        if (is_array($values) and count($values) == 1) {
                            $result = $_model->findWhere(array($_byFields, '=', $values));
                        } elseif (is_array($values) and $this->_isOrdered($values)) {
                            reset($values);
                            $result = $_model->findWhere(array($_byFields, 'BETWEEN', array(current($values), end($values))));
                        } else {
                            $result = $_model->findWhere(array($_byFields, 'IN', $values));
                        }

                        foreach ($collection as $row) {
                            /* @var $row TModel */
                            /* @var $result Collection */
                            //$_related = $row->{$parentRelationData->relationName};
                            $_related = $row;
                            foreach ($innerGet as $deeperRelationName) {
                                $_related = $_related->{$deeperRelationName};
                            }
                            if ($currentRelation->relationType == self::HAS_MANY) {
                                if ($_related instanceof Collection) {
                                    foreach ($_related as $_rr) {
                                        $_one = $result->getRowsByColumnValue($currentRelation->byField, $_rr->{$_rr->getFirstPK()});
                                        if ($_one !== null) {
                                            $_rr->populateRelation($subRelName, $_one);
                                        }
                                    }
                                } else {
                                    $_related->populateRelation($subRelName, $result->getRowsByColumnValue($currentRelation->byField, $_related->{$_related->getFirstPK()}));
                                }
                            } else {
                                //var_dump(count($_related));
                                #var_dump('has one gluing');
                                #var_dump($currentRelation->byField, $subRelName);
                                if (!$_related instanceof Collection) {
                                    $_related = array($_related);
                                }

                                foreach ($_related as $_rr) {
                                    /* @var $_rr TModel */

                                    if (!$_rr instanceof TModel) {
                                        throw new Exception('Collection value is not from proper class ' . var_export($_rr, true));
                                    }

                                    $_byValue = $currentRelation->relationType == self::BELONGS_TO ? $_rr->{$currentRelation->byField} : $_rr->{$_rr->getFirstPK()};

                                    $_one = $result->getRowsByColumnValue($_byFields, $_byValue)->getFirst();
                                    if ($_one !== null) {
                                        $_rr->populateRelation($subRelName, $_one);
                                    }
                                    //var_dump($_rr->{$_rr->getPK()});
                                }
                            }
                        }
                    }
                    break;
                default:
                    #var_dump('unknown relation ' . $currentRelation->relationType);
                    throw new Exception('Unknown relation [__applyEagerForSpecificSubRelation] ' . $currentRelation->relationType);
                    break;
            }

            #var_dump('--------------------------');
//            foreach($subRelations as $subRelName2 => $subRelations2) {
//                var_dump($relationDepthString . '.' . $subRelName, $subRelName2, $subRelations2);
//                $this->__applyEagerForSpecificSubRelation($currentRelation, $subRelations2, $relationDepthString . '.' . $subRelName, $collection);
//            }
            if (count($subRelations)) {
                #var_dump('have subrelations', $subRelations);
                $this->__applyEagerForSpecificSubRelation($currentRelation, $subRelations, $relationDepthString . '.' . $subRelName, $collection);

            }
            //$this->__applyEagerForSpecificSubRelation($subRelName, $subRelations, null);
        }
    }

    // eager

    /**
     * @param array $with
     * @return $this
     * @throws Exception
     */
    public function with($with)
    {
        if (func_num_args() > 1) {
            throw new \Exception('->with() need to have only one param (single string of array of strings');
        }

        if (empty($with)) {
            return $this;
        }
        if (is_array($with)) {
            foreach ($with as $relationName) {
                $this->with($relationName);
            }
        } else {
            if (!in_array($with, $this->_eagers)) {

                if (stripos($with, '.') > 0) {
                    $classes = explode('.', $with);

                    $searchedClass = $this;

                    // todo: switch to self::$__oop_relations
                    for ($i = 0; $i < count($classes) - 1; $i++) {
                        if ($searchedClass->haveRelation($classes[$i])) {
                            $class = $searchedClass->getRelationClass($classes[$i]);
                            $searchedClass = self::model($class);
                        } else {
                            throw new Exception("Relation {$classes[$i]} ({$with}) not found in model " . get_class($searchedClass));
                        }
                    }

                    /* @var $searchedClass TModel */
                    if ($searchedClass->haveRelation($classes[$i])) {
                        $this->_eagers[] = $with;
                    } else {
                        throw new Exception("Relation {$classes[$i]} ({$with}) not found in model " . get_class($searchedClass));
                    }

                } else {
                    if ($this->haveRelation($with)) {
                        $this->_eagers[] = $with;
                    } else {
                        throw new Exception("Relation $with not found in model " . get_class($this));
                    }
                }
            }
        }

        return $this;
    }

    protected function _getWhereByPK()
    {
        $r = array();
        foreach ($this->_primaryKey as $pk) {
            $r[] = array($pk, '=', $this->_values[$pk]);
        }
        return $r;
    }

    /**
     * Delete that record from database
     *
     * @return null
     * @throws DbError
     */
    public function delete()
    {
        DbQuery::sql()
            ->delete()
            ->from($this->_table)
            ->where($this->_getWhereByPK())
            ->execute();

        return true; //TODO: change code that no more operations will be possible on that model
    }

    // those below should be done on collection maybe
    /*public function deleteAll()
    {

    }

    public function deleteByPK($value)
    {
        return $this->delete($this->_primaryKey, $value);
    }

    public function deleteBy($key, $value)
    {

    }*/

    /**
     * Inserts current model values to database as new row
     *
     * @return bool
     */
    protected function _insert()
    {
        DbQuery::sql()
            ->insert()
            ->into($this->_table)
            ->fields($this->_getModifiedFields())
            ->execute();

        // TODO fix so autoincrement value is used instead of first PK - especially for tables which doesn't have PK!
        $this->_values[$this->getFirstPK()] = Core::app()->db->lastId();

        return true;
    }

    /**
     * Updates current model values in database under same primary key
     *
     * @param bool $force
     * @return bool
     * @throws DbError
     */
    // TODO: check that where() automatically will be always good - it should be...
    protected function _update($force = false)
    {
        $values = $this->_getModifiedFields($force);
        foreach($this->_primaryKey as $pk) {
            if (array_key_exists($pk, $values)) {
                unset($values[$pk]);
            }
        }

        if (!empty($values)) {

            DbQuery::sql()
                ->update()
                ->from($this->_table)
                ->fields($values)
                ->where($this->_getWhereByPK())
                ->execute();

        }

        return true;
    }

    public function isModified()
    {
        return count($this->_modified) > 0;
    }

    public function getModified()
    {
        $diff = array();
        foreach ($this->_modified as $field) {
            $diff[$field] = array($this->_original[$field], $this->_values[$field]);
        }
        return $diff;
    }

    public function isNewRecord()
    {
        return $this->_isNewRecord;
    }

    protected function _getModifiedFields($force = false)
    {
        $modified = array();
        if ($force) {
            foreach ($this->_fields as $field) {
                if (!in_array($field, $this->_primaryKey)) {
                    $modified[$field] = $this->_values[$field];
                }
            }
        } else {
            foreach ($this->_modified as $v) {
                $modified[$v] = $this->_values[$v];
            }
        }

        return $modified;
    }

    public function beforeCreate()
    {
        return true;
    }

    public function afterCreate()
    {
        return true;
    }

    public function beforeUpdate()
    {
        return true;
    }

    public function afterUpdate()
    {
        return true;
    }

    public function beforeSave()
    {
        return true;
    }

    public function afterSave()
    {
        return true;
    }

    protected function _getDateFormat()
    {
        return time();
        //return date('Y-m-d H:i:s', time());
    }

    /**
     * @param bool $forceToSave
     * @return bool
     * @throws DbError
     */
    public function save($forceToSave = false)
    {
        if ($this->timestamps) {
            if ($this->_isNewRecord && in_array('created_at', $this->_fields) && $this->created_at == 0) {
                $this->created_at = $this->_getDateFormat();
            }

            if (in_array('updated_at', $this->_fields)) {
                $this->updated_at = $this->_getDateFormat();
            }
        }

        Core::event(strtolower(get_called_class()) . '_before_save', $this);

        $result = $this->_isNewRecord ? $this->beforeCreate() : $this->beforeUpdate();

        if ($result && $this->beforeSave()) {
            if (!empty($this->_modified) or $forceToSave) {
                $result = ($this->_isNewRecord) ? $this->_insert() : $this->_update($forceToSave);
                if ($result == true) {
                    $result = $this->afterSave();
                }

                if ($result == true) {
                    $result = $this->_isNewRecord ? $this->afterCreate() : $this->afterUpdate();
                }

                if ($this->_isNewRecord && $result == true) {
                    $this->_isNewRecord = false;
                }

                Core::event(strtolower(get_called_class()) . '_after_save', $this);

                return $result;
            } else {
                return true;
            }
        }

        throw new DbError('Cannot save record [beforeSave error]');
    }

    /**     * */
    public function validate()
    {
        if ($this->hasErrors()) {
            return false;
        }

        $this->_filter();

        $valid = true;
        foreach ($this->_rules as $field => $entry) {
            foreach ($entry['rules'] as $rule) {

                if (!in_array($field, $this->_fields)) {
                    $this->_errors[$field][] = 'FATAL: Unknown field in model rules: ' . $field . '.';
                    continue;
                }

                switch ($rule) {
                    case 'required':
                        if (empty($this->_values[$field]) && $this->_values[$field] == null && ($this->_schema->getColumn($field)->phpType !== 'integer' && $this->_values[$field] == 0)) {
                            $valid = false;
                            $this->_errors[$field][] = 'Required';
                        }
                        break;
                    case 'int':
                        if (!is_numeric($this->_values[$field]) and ($this->_schema->getColumn($field)->allowNull == false)) {
                            $valid = false;
                            $this->_errors[$field][] = 'Needs to be a number';
                        }
                        break;
                    case 'len':
                        $maxLen = (empty($entry['params'][$rule]['maxlen'])) ? 255
                            : $entry['params'][$rule]['maxlen'];
                        if (strlen($this->_values[$field]) > $maxLen) {
                            $valid = false;
                            $this->_errors[$field][] = 'Cannot be longer than ' . $maxLen;
                        }
                        break;
                    case 'null':
                        if (empty($this->_values[$field])) {
                            $this->_values[$field] = null;
                        }
                        break;
                    default:
//                        var_dump($rule);
                        break;
                }
            }
        }
        return $valid;
    }

    public function filters()
    {
        return array();
    }

    public function _filter()
    {
        foreach ($this->filters() as $filters) {
            if (count($filters) !== 2) {
                throw new Exception('Error in filter for ' . __CLASS__);
            }
            //var_dump($filters);
            list($rows, $filter) = $filters;
            if (!is_array($rows)) {
                $rows = array($rows);
            }

            foreach ($rows as $field) {
                switch ($filter) {
                    case 'trim':
                        if (!empty($this->_values[$field])) {
                            $try = trim($this->_values[$field]);
                            if ($try !== $this->_values[$field]) {
                                if (!in_array($field, $this->_modified) && $try != $this->_values[$field]) {
                                    $this->_modified[] = $field;
                                }
                                $this->_values[$field] = $try;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }

        }
    }

    public function getErrors()
    {
        $resultErrors = array();
        foreach ($this->_errors as $field => $errors) {
            $resultErrors[] = $this->getAttributeLabel($field) . ': ' . implode(', ', $errors);
        }

        return $resultErrors;
        //return implode('<hr/>', $resultErrors);
    }

    public function addError($field, $error)
    {
        $this->_errors[$field][] = $error;
    }

    public function addErrors($errors = array())
    {

    }

    public function getErrorsField($field)
    {
        if (array_key_exists($field, $this->_errors)) {
            return $this->_errors[$field];
        }

        return array();
    }

    public function hasErrorsField($field)
    {
        return array_key_exists($field, $this->_errors);
    }

    public function hasErrors()
    {
        return (!empty($this->_errors));
    }

    /**
     * TODO: empty body
     */
    public function clearErrors()
    {

    }

    /**
     * Returns list of fields in this table
     * Can be overriden to skip using/validating some fields in model
     * @return array
     */
    public function getFields()
    {
        return $this->_schema->getColumnNames();
    }

    /**
     * TODO: empty body
     */
    public function attributeLabels()
    {

    }

    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        if (isset($labels[$attribute])) {
            return $labels[$attribute];
        } else {
            return $this->generateAttributeLabel($attribute);
        }
    }

    /**
     * (c) YiiFramework
     * Generates a user friendly attribute label.
     * This is done by replacing underscores or dashes with blanks and
     * changing the first letter of each word to upper case.
     * For example, 'department_name' or 'DepartmentName' becomes 'Department Name'.
     *
     * @param string $name the column name
     *
     * @return string the attribute label
     */
    public function generateAttributeLabel($name)
    {
        return ucwords(
            trim(strtolower(str_replace(array('-', '_', '.'), ' ', preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name))))
        );
    }

    public function getIsNewRecord()
    {
        return $this->_isNewRecord;
    }

    /**
     * TODO empty body
     */
    public function scopes()
    {

    }

    /**
     * BELONGS_TO: e.g. a member belongs to a team;
     * HAS_ONE: e.g. a member has at most one profile;
     * HAS_MANY: e.g. a team has many members;
     * MANY_MANY: e.g. a member has many skills and a skill belongs to a member.
     *
     * @return array
     */
    public function relations()
    {
        return array();
    }

    public function __get($value)
    {
        if (in_array($value, $this->_fields)) {
            if (array_key_exists($value, $this->_values)) {
                return $this->_values[$value];
            } else {
                return null;
            }
        } else {
            if (isset($this->_related[$value])) {
                return $this->_related[$value];
            } else {
                if (array_key_exists($value, $this->_relations)) {
//			          var_dump('relation ' . __CLASS__);
                    return $this->getRelated($value);
                } else {
                    $getter = 'get' . ucfirst($value);
                    if (method_exists($this, $getter)) {
                        return $this->$getter();
                    }
//                    else {
//                        if (Core::app()->mode != Core::MODE_PROD) {
//                            throw new Exception('No method <code>' . $getter . '</code> or property <code>' . $value . '</code> in <code>' . get_class($this) . '</code>.');
//                        }
//                    }
                }
            }
        }
        return null;
    }

    public function __call($name, $args = array())
    {
        $type = substr($name, 0, 3);

        switch ($type) {
            case 'get':
                $value = substr($name, 3);

                if (in_array($value, $this->_fields)) {
                    if (array_key_exists($value, $this->_values)) {
                        return $this->_values[$value];
                    } else {
                        return null;
                    }
                } else {
                    if (isset($this->_related[$value])) {
                        return $this->_related[$value];
                    }
                }

                throw new Exception('No property <code>' . $value . '</code> called by method <code>' . get_class($this) . '->' . $name . '</code>.');

                break;
        }

        return null;
    }

    public function getData()
    {
        return $this->_values;
    }

    public function __set($name, $value)
    {
        $setter = 'set' . ucfirst($name);
        if (array_key_exists($name, $this->_values)) {
            $value = $this->_schema->columns[$name]->typecast($value);
            //TODO: choose that it should be != or !== for $value=$this->_values compare
            if ($value !== $this->_values[$name]) {
                $this->_modified[] = $name;
                $this->_values[$name] = $value;
            }
        } else {
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }

    /**
     * @param $values
     * @return $this
     */
    public function setAttributes($values)
    {
        if (is_array($values)) {
            // TODO: default value setting by column type//probablu moved now to populate
            foreach ($values as $field => $value) {
                $this->__set($field, $value);
            }
        } else {
            //TODO: throw error or no?
        }

        return $this;
    }

    protected function _populateOnNewRecord()
    {
        return false;
    }

    protected function _populate($attributes = array())
    {
        if (!is_array($attributes)) {
            $attributes = array();
        }

        foreach ($this->_fields as $fieldName) {
            if (array_key_exists($fieldName, $attributes)) {
                $this->_values[$fieldName] = $this->_original[$fieldName] = $value = $this->_schema->columns[$fieldName]->typecast($attributes[$fieldName]);
            } else {
                $this->_values[$fieldName] = $this->_original[$fieldName] = $this->_schema->columns[$fieldName]->defaultValue;
            }
        }
    }

    public function populateRelation($relationName, $records)
    {
        $this->_related[$relationName] = $records;
    }

    public static function haveRelationForClass($class)
    {
        if (array_key_exists($class, self::$_oop_relations)) {
            return count(self::$_oop_relations[$class]) > 0;
        } else {
            self::model($class);
            return self::haveRelationForClass($class);
        }
        return false;
    }

    /**
     * @param $class
     * @param $relation
     * @return TModelRelation|null
     */
    public static function getRelationForClass($class, $relation)
    {
        if (self::haveRelationForClass($class)) {
            if (array_key_exists($relation, self::$_oop_relations[$class])) {
                return self::$_oop_relations[$class][$relation];
            }
        }

        return null;
    }

    public function haveRelation($name)
    {
        return (array_key_exists($name, $this->_relations));
    }

    public function getRelated($relationName)
    {
        return $this->getRealatedCached($relationName);
        //return $this->_getRelated($relationName);
    }

    public function getRealatedCached($relationName)
    {
        if (array_key_exists($relationName, $this->_related)) {
            return $this->_related[$relationName];
        } else {
            return $this->_getRelated($relationName);
        }
    }

    public function getRelationClass($relationName)
    {
        return $this->_getRelatedData($relationName, 1);
    }

    public function getRelationRelatedField($relationName)
    {
        return $this->_getRelatedData($relationName, 2);
    }

    protected function _getRelatedData($relationName, $field)
    {
        return (array_key_exists($relationName, $this->_relations)) ? $this->_relations[$relationName][$field] : null;
    }

    protected function _getRelated($relationName, $populate = true, $customValues = null)
    {
        // todo: if called again, will load again! should be fixed
        $result = null;
        switch ($this->_relations[$relationName][0]) {
            case self::HAS_MANY:
                $rel = self::model($this->_relations[$relationName][1]);
                $conditions = array();
                if (array_key_exists(3, $this->_relations[$relationName])) {
                    $conditions = $this->_relations[$relationName][3];
                }
                if (empty($customValues)) {
                    $customValues = $this->_values[$this->getFirstPK()];
                }

                $with = array();
                if (array_key_exists('with', $conditions)) {
                    $with = $conditions['with'];
                    unset($conditions['with']);
                }

                if (is_array($customValues)) {
                    sort($customValues);
                }
                if (is_array($customValues) and count($customValues) == 1) {
                    $result = $rel->with($with)->findWhere(array($this->_relations[$relationName][2], '=', $customValues[0]), -1, 0, $conditions);
                } elseif (is_array($customValues) and $this->_isOrdered($customValues)) {
                    reset($customValues);
                    $result = $rel->with($with)->findWhere(array(array($this->_relations[$relationName][2], 'BETWEEN', array(current($customValues), end($customValues)))), -1, 0, $conditions);
                } else {
                    $result = $rel->with($with)->findWhere(array(array($this->_relations[$relationName][2], 'IN', $customValues)), -1, 0, $conditions);
                }
                break;
            case self::BELONGS_TO:
                $rel = self::model($this->_relations[$relationName][1]);
                if (empty($customValues)) {
                    $customValues = $this->_values[$this->_relations[$relationName][2]];
                }
                $result = $rel->findWhere(array($rel->getFirstPK(), 'IN', $customValues));
                if ($populate) {
                    $result = $result->getFirst();
                }
                break;
            default:
                throw new DbError('Unknown relation type ' . $this->_relations[$relationName][0]);
        }

        if ($populate) {
            $this->_related[$relationName] = $result;
            return $this->_related[$relationName];
        } else {
            return $result;
        }
    }

    public function getAlreadyLoadedRelations()
    {
        return array_keys($this->_related);
    }

    protected function _isOrdered($array)
    {
        $i = 0;
        $total_elements = count($array);
        $way = 0;

        if ($total_elements > 1) {
            $way = $array[0] - $array[1];
            $way = ($way > 0) - ($way < 0);
        }

        while ($total_elements > 1) {
            if (($array[$i] - $array[$i + 1]) == $way) {
                $i++;
                $total_elements--;
            } else {
                return false;
            }
        }

        return true;
    }

    public function __toString()
    {
        $head = '';
        $row = '';
        foreach ($this->_values as $k => $v) {
            $head .= ($k == $this->getFirstPK()) ? '<th><u>' . $k . '</u></th>' : '<th>' . $k . '</th>';

            $modified = (!$this->isNewRecord() and in_array($k, $this->_modified));
            $new = ($this->isNewRecord());

            $row .= '<td' . ($modified ? ' style="background: pink;"' : ($new ? ' style="background: greenyellow;"' : '')) . '>';
            if ($modified) {
                $row .= '<span style="color:red;">';
                $row .= (($this->_original[$k] === null) ? ('<em>null</em>') : wordwrap($this->_original[$k], 50, '<br>', true));
                $row .= '</span><br>';
            }
            $row .= (($v === null) ? ('<em>null</em>') : wordwrap($v, 50, '<br>', true));
            $row .= '</td>';
        }

        $headerCount = count($this->_values);

        foreach ($this->_relations as $relationName => $relation) {
            ///echo $relation[1];
            switch ($relation[0]) {
                case self::HAS_ONE:
                    echo $relation[1];
                    break;
                case self::BELONGS_TO:
                    if (!empty($this->_related[$relationName])) {
                        $head .= '<th rowspan="2" style="vertical-align: middle;">';
                        $head .= 'belongs to model <kbd>' . $relation[1] . '</kbd>';
                        $head .= '<br/>relation name is <kbd>' . $relationName . '<kbd>';
                        $head .= '</th>';
                        $headerCount++;

                        foreach ($this->_related[$relationName]->getFields() as $v) {
                            $headerCount++;
                            $head .= '<th>' . $v . '</th>';
                            $row .= '<td>' . $this->_related[$relationName]->$v . '</td>';
                        }
                    }
                    break;
            }
        }

        $head = '<th colspan="' . $headerCount . '">' . get_called_class() . ($this->isNewRecord() ? ' - NEW' : '') . '</th></tr><tr>' . $head;

        return /* 'Data in ' . get_called_class() . ' model instance:<br/>' . */
            '<table border="1" cellspacing="0"><tr>' . $head . '</tr><tr valign="top">' . $row . '</tr></table>';
    }

    /**
     * Returns an iterator for traversing the attributes in the model.
     * This method is required by the interface IteratorAggregate.
     *
     * @return ArrayIterator an iterator for traversing the items in the list.
     */
    public function getIterator()
    {
        $attributes = $this->_values;
        return new ArrayIterator($attributes);
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param mixed $offset the offset to check on
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_values); //property_exists($this, $offset);
    }

    public function __isset($offset)
    {
        return $this->offsetExists($offset);
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param integer $offset the offset to retrieve element.
     *
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->_values[$offset] : null;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param integer $offset the offset to set element
     * @param mixed $item the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->$offset = $item;
    }

    /**
     * Unsets the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

}
