<?php

class UsersController extends AdminController
{

    public function indexAction()
    {
        //TODO: when there wasn't admin folder, content view was loaded...?

        $users = User::model()->findAll(30);

        $this->render('list', array('users' => $users));
    }

    public function editAction($id)
    {
        $model = User::model()->load($id);

        if ($this->request->isPost()) {
            $model->attributes = $this->request->getPost('User');
            if ($model->validate()) {
                $model->save();
                return $this->redirect(array('users'));
            }
        }

        $this->render('edit', array('model' => $model));
    }
}
