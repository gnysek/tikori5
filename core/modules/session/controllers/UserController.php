<?php

class UserController extends Controller
{

    public function loginAction()
    {
        if ($this->request->isPost()) {

            if (User::model()->login($_POST['Login'])) {
                $this->render('login-success');
            } else {
                $this->render('login-failure');
            }
        } else {
            $this->render('login');
        }
    }
}
