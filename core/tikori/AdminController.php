<?php


class AdminController extends Controller
{

    protected function _beforeRun()
    {
        if ($this->area == 'admin') {
            if (!Core::app()->session->admin()) {
                $this->redirect('//');
//                return $this->forward401();
                return false;
            }
        }
        return parent::_beforeRun();
    }
}
