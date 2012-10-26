<?php

class DefaultController extends Controller {

	public function indexAction() {
		$model = Team::model()->find(1);
		/* @var $model Team */
		$this->render('body', array('data' => $model->users));
	}

	public function oneAction($id = 0) {
		echo 'one';
	}

	public function twoAction($id) {
		echo 'two';
		echo $id;
	}

}
