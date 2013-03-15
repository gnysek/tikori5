<?php

class ContentAuth extends AdminController
{


//    protected function _beforeRun()
//    {
//        if ($this->area == 'admin') {
//            // set auth
//            $this->checkPermissions = true;
//        }
//
//        return parent::_beforeRun();
//    }

    public function checkPermissions()
    {
        //$this->_beforeRun();
        parent::checkPermissions();
    }

    public function getPermissions()
    {
        return array(
            '?' => 'deny',
            '*' => 'deny',
            '@' => 'allow',
        );
    }
}
