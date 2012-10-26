<?php

class DefaultController extends Controller {

	public function indexAction() {
		$model = Team::model()->find(1);
		/* @var $model Team */
		$this->render('body', array(
			'test1' => $model->users,
			'test2' => $model->users[0]->team0
		));
	}

	public function oneAction($id = 0) {
		echo 'one';
	}

	public function twoAction($id) {
		echo 'two';
		echo $id;
	}

}
