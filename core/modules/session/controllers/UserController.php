<?php

class UserController extends Controller
{

    public function loginAction()
    {
        if (Core::app()->session->logged_in) {
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
        if (Core::app()->session->logged_in) {
            return $this->redirect('/user/profile');
        }
    }

    public function logoutAction()
    {
        if (Core::app()->session->logged_in) {
            Core::app()->session->logout();
        }

        $this->redirect('/');
    }

    public function profileAction()
    {
        $this->render('profile');
    }
}
