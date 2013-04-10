<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 15.01.13
 * Time: 11:46
 * To change this template use File | Settings | File Templates.
 */

class Admin_ContentController extends ContentController
{

    public function editAction($id = 0)
    {
        $model = ContentTranslation::model()->load($id);

        if ($this->request->isPost()) {
            $model->attributes = $this->request->getPost('ContentTranslation');
            if ($model->validate()) {
                $model->save();
                return $this->redirect(array('content', 'node' => $id));
            }
        }

        $this->render('edit', array('model' => $model));
    }

    public function deleteAction()
    {

    }
}
