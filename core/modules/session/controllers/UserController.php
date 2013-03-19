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
        if (!Core::app()->session->authenticated()) {
            return $this->redirect('/user/login');
        }

        $changed = false;
        $user = Core::app()->session->user();
        if ($this->request->isPost()) {
            $user->attributes = $this->request->params('User');
            $user->save();
            $changed = true;
        }
        $this->render('profile', array('model' => $user, 'changed' => $changed));
    }
}
