<?php

/**
 * Class Model
 *
 * @property string $tableName  Table Name
 * @property $this  $attributes Attribute values
 */
abstract class Model implements IteratorAggregate, ArrayAccess
{

    const BELONGS_TO = 'BELONGS_TO';
    const HAS_ONE = 'HAS_ONE';
    const HAS_MANY = 'HAS_MANY';
    const STATS = 'STATS';

//	const MANY_MANY = 4;

    protected $_table = '';
    protected $_fields = array();
    protected $_values = array();
    protected $_rules = array();
    protected $_related = null;
    protected $_primaryKey = 'id';
    protected $_scopes = array();
    protected $_relations = array();
    protected $_isNewRecord = true;
    protected $_errors = array();
    public $_modified = array();
    public $tableName = '';
    public $_new = false;
    public $_eagers = array();

    public function __construct()
    {
        $this->_scopes = $this->scopes();
        $this->_relations = $this->relations();
        $this->_fields = $this->getFields();
        if (!empty($this->_fields)) {
            foreach ($this->_fields as $v) {
                $this->_values[$v] = null;
            }
        }
        $this->_rules = $this->_prepareRules();
        $this->_table = $this->getTable();
        $this->_primaryKey = $this->getPK();

        $info = DbQuery::sql()->getTableInfo($this->getTable());
        //$this->_new = true;
    }

    public function getTable()
    {
        if (empty($this->_table)) {
            return strtolower(get_called_class());
        }
        return $this->_table;
    }

    public function getPK()
    {
        return $this->_primaryKey;
    }

    /**
     * @param string $model
     *
     * @return Model
     */
    public static function model($model = null)
    {
        if ($model == null) {
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
     * @return $this|Model
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
                    $this->_values[$field] = $result[0]->$field;
                }
            }
            $this->_isNewRecord = false;
        } else {
            return null;
        }
        return $this;
    }

    // duplicate of load in fact...
//	public function findByPK($value) {
//		return $this->findBy($this->_primaryKey, $value);
//	}

    public function findBy($key, $value, $onlyFirst = false)
    {
        // it's now made in DbQuery
//        if (!is_numeric($value)) {
//            $value = Core::app()->db->protect($value);
//        }
        $sql = DbQuery::sql()->select()->from($this->_table)->where(array($key, '=', $value));
        $result = $sql->execute();
        $return = array();
        foreach ($result as $row) {
            /* @var $row Result */
            $c = self::model(get_called_class());
            $c->setValues($row->getIterator()->getArrayCopy());

            if ($onlyFirst) {
                return $c;
            }

            $return[] = $c;
        }
        return $return;
//		return $this;
    }

    public function findWhere($where = null, $limit = -1, $offset = 0, $conditions = array())
    {
        $sql = DbQuery::sql()->select()->from($this->_table);
        if (!empty($where)) {
            $sql->where($where);
        }

        $sql->limit($limit, $offset);

        if (!empty($conditions)) {
            $sql->conditions($conditions);
        }

        $result = $sql->execute();
        $return = array();
        foreach ($result as $row) {
            /* @var $row Result */
            $c = self::model(get_called_class());
            $c->setValues($row->getIterator()->getArrayCopy());

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
                            /* @var Model $row */
                            $row->populateRelation($relationName, $toAssign);
                        }
                        break;
                    case self::BELONGS_TO:
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
                        break;
                    default:
                        throw new Exception('Not yet implemented');
                        break;
                }
                ///var_dump($this->_getRelated($relationName, array('1')));
            }
        }

        return $collection;
    }

    public function findAll($limit = -1, $offset = 0, $conditions = array())
    {
        return $this->findWhere(null, $limit, $offset, $conditions);
    }

    public function count($by = null)
    {
        $result = DbQuery::sql()->select('COUNT(*) AS tikori_total')->from($this->_table)->execute();
        return (!empty($result[0])) ? $result[0]->tikori_total : 0;
    }

    // eager
    public function with($with)
    {
        if (is_array($with)) {
            foreach ($with as $relationName) {
                $this->with($relationName);
            }
        } else {
            if (!in_array($with, $this->_eagers) && array_key_exists($with, $this->_relations)) {
                $this->_eagers[] = $with;
            }
        }

        return $this;
    }

    /**     * */
    public function delete()
    {

    }

    public function deleteAll()
    {

    }

    public function deleteByPK($value)
    {
        return $this->delete($this->_primaryKey, $value);
    }

    public function deleteBy($key, $value)
    {

    }

    /**     * */
    public function insert()
    {
        DbQuery::sql()->insert()->from($this->_table)->fields($this->_getModifiedFields())->execute();
        $this->_values[$this->getPK()] = Core::app()->db->lastId();

        return true;
    }

    // TODO: check that where() automatically will be always good - it should be...
    public function update()
    {
        $values = $this->_getModifiedFields();
        if (array_key_exists($this->_primaryKey, $values)) {
            unset($values[$this->_primaryKey]);
        }
        DbQuery::sql()
            ->update()
            ->from($this->_table)
            ->fields($values)
            ->where(array($this->_primaryKey, '=', $this->_values[$this->_primaryKey]))
            ->execute();

        return true;
    }

    protected function _getModifiedFields()
    {
        $modified = array();
        foreach ($this->_modified as $v) {
            $modified[$v] = $this->_values[$v];
        }

        return $modified;
    }

    public function beforeSave()
    {
        return true;
    }

    public function afterSave()
    {
        return true;
    }

    public function save($forceToSave = false)
    {
        if ($this->beforeSave()) {
            if (!empty($this->_modified) or $forceToSave) {
                $result = ($this->_isNewRecord) ? $this->insert() : $this->update();
                if ($result == true) {
                    $result = $this->afterSave();
                }
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

        $valid = true;
        foreach ($this->_rules as $field => $entry) {
            foreach ($entry['rules'] as $rule) {

                if (!in_array($field, $this->_fields)) {
                    $this->_errors[$field][] = 'FATAL: Unknown field in model rules: ' . $field . '.';
                    continue;
                }

                switch ($rule) {
                    case 'required':
                        if (empty($this->_values[$field])) {
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

    public function getErrors()
    {
        $resultErrors = array();
        foreach ($this->_errors as $field => $errors) {
            $resultErrors[] = $this->getAttributeLabel($field) . ': ' . implode('<br/>', $errors);
        }

        return implode('<hr/>', $resultErrors);
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

    public abstract function getFields();

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
     * @return type
     */
    public function relations()
    {
        return array();
    }

    public function __get($value)
    {
        /* if (__CLASS__ == 'Member') {
          var_dump($value);
          var_dump($this->_relations);
          } */
        $getter = 'get' . ucfirst($value);
        if (isset($this->_values[$value])) {
            return $this->_values[$value];
        } else {
            if (isset($this->_related[$value])) {
                return $this->_related[$value];
            } else {
                if (array_key_exists($value, $this->_relations)) {
//			var_dump('relation ' . __CLASS__);
                    return $this->getRelated($value);
                } else {
                    if (method_exists($this, $getter)) {
                        return $this->$getter();
                    } else {
//                        if (Core::app()->mode != Core::MODE_PROD) {
//                            return '<span style="color: red;">' . $value . ' IS UNDEFINED!</span>';
//                        }
                        return null;
                    }
                }
            }
        }
    }

    public function __set($name, $value)
    {
        $setter = 'set' . ucfirst($name);
        if (array_key_exists($name, $this->_values)) {
            //TODO: choose that it should be != or !== for $value=$this->_values compare
            if (!in_array($name, $this->_modified) && $value != $this->_values[$name]) {
                $this->_modified[] = $name;
            }
            $this->_values[$name] = $value;
        } else {
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }

    public function setAttributes($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (in_array($k, $this->_fields)) {
                    $this->__set($k, $v);
                }
            }
        } else {
            //TODO: throw error or no?
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

    private function _getRelated($relationName, $populate = true, $customValues = null)
    {
        $result = null;
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
                        $head .= '<th rowspan="2" style="vertical-align: middle;">belongs to ' . $relation[1] . '</th>';
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
        return property_exists($this, $offset);
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
        return $this->$offset;
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
