<?php

class ErrorTestController extends Controller
{

    public function noticeAction()
    {
        echo $this->renderPartial('test/notice');
    }

    public function parseAction()
    {
        echo $this->renderPartial('test/parse');
    }

    public function fatalAction()
    {
        $this->renderPartial('test/fatal');
    }

    public function userAction() {
        $this->renderPartial('test/user');
    }
}
