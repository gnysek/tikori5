<?php

class UserController extends Controller
{

    public function loginAction()
    {
        if ($this->request->isPost()) {
            var_dump($_POST);
        }
        $this->render('login');
    }
}
