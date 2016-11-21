<?php

/**
 * Class Model
 *
 * @property string $tableName  Table Name
 * @property array  $attributes Attribute values
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
    protected $_related = NULL;
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

        // set default values
        if (!empty($this->_fields)) {
            foreach ($this->_fields as $fieldName) {
                if (array_key_exists($fieldName, $this->_relations)) {
                    throw new DbError('Model ' . get_class($this) . ' have field named same as relation: ' . $fieldName);
                }
                $this->_values[$fieldName] = $this->_original[$fieldName] = ($fieldName == $this->getPk()) ? NULL : ($this->_schema->getColumn($fieldName)->defaultValue);
            }
        }
        $this->_rules = $this->_prepareRules();
        $this->_table = $this->getTable();
        $this->_primaryKey = $this->getPK();
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

    public static function getTableName($modelName) {
        return strtolower($modelName);
    }

    public function getPK()
    {
        return $this->_primaryKey;
    }

    /**
     * @param string $model
     *
     * @return TModel
     */
    public static function model($model = NULL)
    {
        if ($model == NULL) {
            $model = get_called_class();
        }
        return new $model();
    }

    public function setValues($values)
    {
        if (is_array($values)) {
            $this->_values = $values;
        }
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
        if (!is_numeric($id)) {
            //$id = DB::protect($id);
        }

        if (is_array($id)) {
            $sql = DbQuery::sql()->select()->from($this->_table)->where(array($this->_primaryKey, 'IN', $id));
        } else {
            $sql = DbQuery::sql()->select()->from($this->_table)->where(array($this->_primaryKey, '=', $id));
        }
        $result = $sql->execute();

        if (!is_array($id) && count($result) > 1) {
            throw new DbError('Returned more than 1 record - PK wrongly defined?');
        }

        if (count($result) == 1) {
            foreach ($this->_fields as $field) {
                if ($result[0]->offsetExists($field)) {
                    $this->_values[$field] = $this->_original[$field] = $result[0]->$field;
                }
            }
            $this->_isNewRecord = false;
        } else {
            return NULL;
        }
        return $this;
    }

    // duplicate of load in fact...
//	public function findByPK($value) {
//		return $this->findBy($this->_primaryKey, $value);
//	}

    public function findBy($key, $value, $onlyFirst = false)
    {
        $results = $this->findWhere(array($key, '=', $value));
        return ($onlyFirst && count($results) >= 1) ? $results[0] : null;
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
     * @param null  $where
     * @param       $limit
     * @param int   $offset
     * @param array $conditions
     * @return Collection
     * @throws Exception
     */
    public function findWhere($where = NULL, $limit = -1, $offset = 0, $conditions = array())
    {
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

            if (!empty($this->_eagers)) {
                foreach ($this->_eagers as $k => $relationName) {
                    switch ($this->_relations[$relationName][0]) {
                        case self::BELONGS_TO:
                            if (!array_key_exists($values[ $this->_relations[$relationName][2] ], $relationCacheByPk)) {
//                                $r = Model::model($this->_relations[$relationName][1]);
                                $rvalues = array();
                                $fields = Core::app()->db->getSchema()->getTableSchema( self::getTableName($this->_relations[$relationName][1]) )->getColumnNames();
                                foreach ($fields as $relationfield) {
                                    $relationfieldName = 'r' . ($k + 1) . '_' . $relationfield;
                                    $rvalues[$relationfield] = $values[$relationfieldName];
                                }
                                $relatedClassName = $this->_relations[$relationName][1];
                                $r = new $relatedClassName($rvalues, false);
                                /* @var $r TModel */
//                                $r->setAttributes($rvalues);
                                $relationCacheByPk[$values[ $this->_relations[$relationName][2] ]] = $r;
                            }

                            $c->populateRelation($relationName, $relationCacheByPk[$values[ $this->_relations[$relationName][2] ]]);
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

        $collection = new Collection($return);

        if (!empty($this->_eagers)) {
            foreach ($this->_eagers as $relationName) {
                $values = array();
                switch ($this->_relations[$relationName][0]) {
                    case self::HAS_MANY:
//                        var_dump($this->getPK());
//                        var_dump($collection->getColumnValues($this->getPK()));
                        $byField = $this->getPK();
                        $related = $this->_getRelated($relationName, false, $collection->getColumnValues($byField));
                        /* @var Collection $related */
//                        var_dump(count($related));
                        foreach ($return as $row) {
//                            var_dump($byField . ' ' . $row->$byField . ' ' . $this->_relations[$relationName][2]);
                            $toAssign = $related->getRowsByColumnValue($this->_relations[$relationName][2], $row->$byField);
//                            var_dump(count($toAssign));
                            /* @var TModel $row */
                            $row->populateRelation($relationName, $toAssign);
                        }
                        break;
                    case self::BELONGS_TO: break;
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

        return $collection;
    }

    /**
     * @param       $limit
     * @param int   $offset
     * @param array $conditions
     * @return Collection[]|Collection
     */
    public function findAll($limit = -1, $offset = 0, $conditions = array())
    {
        return $this->findWhere(NULL, $limit, $offset, $conditions);
    }

    public function count($by = NULL)
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
        return (!empty($result[0])) ? $result[0]->tikori_total : 0;
    }

    protected function _applyEagers(DbQuery $sql) {
        if (!empty($this->_eagers)) {
            foreach ($this->_eagers as $relationName) {
                switch ($this->_relations[$relationName][0]) {
                    case self::BELONGS_TO:

                        $model = TModel::model($this->_relations[$relationName][1]);
                        /* @var $model TModel */
                        $sql->joinOn($model->getTable(), array($model->getPK(), '=', $this->_relations[$relationName][2]));

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

    // eager
    /**
     * @param $with
     * @return $this
     * @throws Exception
     */
    public function with($with)
    {
        if (is_array($with)) {
            foreach ($with as $relationName) {
                $this->with($relationName);
            }
        } else {
            if (!in_array($with, $this->_eagers)) {
                if (array_key_exists($with, $this->_relations)) {
                    $this->_eagers[] = $with;
                } else {
                    throw new Exception("Relation $with not found in model " . get_class($this));
                }
            }
        }

        return $this;
    }

    /**
     * Delete that record from database
     *
     * @return null
     */
    public function delete()
    {
        DbQuery::sql()
            ->delete()
            ->from($this->_table)
            ->where(array($this->_primaryKey, '=', $this->_values[$this->_primaryKey]))
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
        $this->_values[$this->getPK()] = Core::app()->db->lastId();

        return true;
    }

    /**
     * Updates current model values in database under same primary key
     *
     * @return bool
     */
    // TODO: check that where() automatically will be always good - it should be...
    protected function _update($force = false)
    {
        $values = $this->_getModifiedFields($force);
        if (array_key_exists($this->_primaryKey, $values)) {
            unset($values[$this->_primaryKey]);
        }

        if (!empty($values)) {

            DbQuery::sql()
                ->update()
                ->from($this->_table)
                ->fields($values)
                ->where(array($this->_primaryKey, '=', $this->_values[$this->_primaryKey]))
                ->execute();

        }

        return true;
    }

    public function isModified()
    {
        return count($this->_modified) > 0;
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
                if ($field != $this->_primaryKey) {
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
                        if (empty($this->_values[$field]) && $this->_values[$field] == NULL && ($this->_schema->getColumn($field)->phpType !== 'integer' && $this->_values[$field] == 0)) {
                            $valid = false;
                            $this->_errors[$field][] = 'Required';
                        }
                        break;
                    case 'int':
                        if (!is_numeric($this->_values[$field])) {
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
                            $this->_values[$field] = NULL;
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

    public function clearErrors()
    {

    }

    /**
     * Returns list of fields in this table
     * Can be overriden to skip using/validating some fields in model
     * @return array
     */
    public function getFields() {
        return $this->_schema->getColumnNames();
    }

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
//			var_dump('relation ' . __CLASS__);
                    return $this->getRelated($value);
                } else {
                    $getter = 'get' . ucfirst($value);
                    if (method_exists($this, $getter)) {
                        return $this->$getter();
                    }
                    /* else {
                    //                        if (Core::app()->mode != Core::MODE_PROD) {
                    //                            return '<span style="color: red;">' . $value . ' IS UNDEFINED!</span>';
                    //                        }
                                            return NULL;
                                        }*/
                }
            }
        }
        return NULL;
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

    public function setAttributes($values)
    {
        if (is_array($values)) {
            // TODO: default value setting by column type//probablu moved now to populate
            foreach(array_keys($values) as $field) {
                if (isset($values[$field])) {
                    $this->__set($field, $values[$field]);
                }
            }
        } else {
            //TODO: throw error or no?
        }
    }

    protected function _populateOnNewRecord() {
        return false;
    }

    protected function _populate($attributes = array()) {
        if (!is_array($attributes)) {
            $attributes = array();
        }

        foreach($this->_fields as $fieldName) {
            if (array_key_exists($fieldName, $attributes)) {
                $this->_values[$fieldName] = $this->_original[$fieldName] = $attributes[$fieldName];
            } else {
                $this->_values[$fieldName] = $this->_original[$fieldName] = $this->_schema->columns[$fieldName]->defaultValue;
            }
        }
    }

    public function populateRelation($relationName, $records)
    {
        $this->_related[$relationName] = $records;
    }

    public function getRelated($relationName)
    {
        return $this->_getRelated($relationName);
    }

    public function getRelationClass($relationName) {
        return $this->_getRelatedData($relationName, 1);
    }

    public function getRelationRelatedField($relationName) {
        return $this->_getRelatedData($relationName, 2);
    }

    protected function _getRelatedData($relationName, $field) {
        return (array_key_exists($relationName, $this->_relations)) ? $this->_relations[$relationName][$field] : null;
    }

    protected function _getRelated($relationName, $populate = true, $customValues = NULL)
    {
        $result = NULL;
        switch ($this->_relations[$relationName][0]) {
            case self::HAS_MANY:
                $rel = self::model($this->_relations[$relationName][1]);
                $conditions = array();
                if (array_key_exists(3, $this->_relations[$relationName])) {
                    $conditions = $this->_relations[$relationName][3];
                }
                if (empty($customValues)) {
                    $customValues = $this->_values[$this->getPK()];
                }
                $result = $rel->findWhere(array(array($this->_relations[$relationName][2], 'IN', $customValues)), -1, 0, $conditions);
                break;
            case self::BELONGS_TO:
                $rel = self::model($this->_relations[$relationName][1]);
                if (empty($customValues)) {
                    $customValues = $this->_values[$this->_relations[$relationName][2]];
                }
                $result = $rel->findWhere(array($rel->getPK(), 'IN', $customValues));
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

    public function __toString()
    {
        $head = '';
        $row = '';
        foreach ($this->_values as $k => $v) {
            $head .= ($k == $this->getPK()) ? '<th><u>' . $k . '</u></th>' : '<th>' . $k . '</th>';
            $row .= '<td>' . $v . '</td>';
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
                        $head .= '<br/>relation name is <kbd>'. $relationName . '<kbd>';
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

        $head = '<th colspan="' . $headerCount . '">' . get_called_class() . '</th></tr><tr>' . $head;

        return /* 'Data in ' . get_called_class() . ' model instance:<br/>' . */
            '<table><tr>' . $head . '</tr><tr>' . $row . '</tr></table>';
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
     * @param mixed   $item   the element value
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

class ModelData
{

}
