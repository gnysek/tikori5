<?php

class UserController extends Controller
{

    public function loginAction()
    {
        if (Core::app()->session->authenticated()) {
            //TODO: maybe here should be access filter, which deny access?
            return $this->redirect('/user/profile');
        }

        if ($this->request->isPost()) {
            if (Core::app()->session->login($_POST['Login'])) {
                $this->render('login-success');
            } else {
                $this->render('login-failure');
            }
        } else {
            $this->render('login');
        }
    }

    public function registerAction()
    {
        if (Core::app()->session->authenticated()) {
            return $this->redirect('/user/profile');
        }

        $this->render('register');
    }

    public function logoutAction()
    {
        if (Core::app()->session->authenticated()) {
            Core::app()->session->logout();
        }

        $this->redirect('/');
    }

    public function profileAction()
    {
        $this->render('profile');
    }
}
