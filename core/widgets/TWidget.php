<?php

class TWidget
{

    public function run()
    {
        echo 'Undefined';
    }

    public function init()
    {

    }

    public function setupProperties($properties)
    {
        foreach ($properties as $k => $v) {
            if (isset($this->$k)) {
                $this->$k = $v;
            }
        }
    }
}
