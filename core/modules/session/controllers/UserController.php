<?php

class UserController extends Controller
{
    public function loginAction()
    {
        if ($this->request->isPost()) {
            $user = User::model()->login($_POST['Login']['login'], $_POST['Login']['pass']);
            var_dump($_POST);
        }
        $this->render('login');
    }
}
