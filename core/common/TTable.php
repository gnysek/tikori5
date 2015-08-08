<?php

class TTable
{

    protected $_data = array();

    function __construct($config)
    {
        $this->_data = array_merge(array('data' => array(), 'columns' => array()), $config);
    }

    public function render()
    {
        echo '<table class="pure-table pure-table-horizontal" style="width: 100%;">';
        echo '<tr>';
        foreach ($this->_data['columns'] as $num => $column) {
            if (is_array($column)) {
                echo '<th>' . ucfirst($column['name']) . '</th>';
            } else {
                echo '<th>' . ucfirst($column) . '</th>';
            }
        }

        foreach ($this->_data['data'] as $id => $data) {
            echo '<tr>';
            foreach ($this->_data['columns'] as $num => $column) {
                if (is_array($column)) {

                    if (!is_callable($column['value'])) {
                        $val = eval('return ' . $column['value'] . ';');
                    } else {
                        $val = ($column['value']($data));
                    }

                    if (!empty($column['class'])) {
                        $className = 'TTable' . ucfirst($column['class']);
                        $c = new $className;
                        $val = $c->render($data, $val, (empty($column['renderOptions'])) ? array() : $column['renderOptions']);
                    }

                    echo '<td>' . $val . '</td>';
                } else {
                    echo '<td>' . $data->$column . '</td>';
                }
            }
            echo '</tr>';
        }
        echo '</tr>';
        echo '</table>';
    }

}
