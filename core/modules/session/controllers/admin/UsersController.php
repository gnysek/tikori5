<?php

class Admin_UsersController extends AdminController
{

    public function indexAction()
    {
        //TODO: when there wasn't admin folder, content view was loaded...?

        $users = User::model()->findAll(30);

        $this->render('list', array('users' => $users));
    }
}
