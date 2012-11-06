<?php

class DefaultController extends Controller {

	public function indexAction() {
		var_dump((string) DbQuery::query()->from('test')->where('id > 1'));
		var_dump((string) DbQuery::query()->update()->from('test')->where('id > 1'));
		var_dump((string) DbQuery::query()->delete()->from('test')->where('id > 1'));
		var_dump((string) DbQuery::query()->insert()->from('test')->fields(array('1', 'test'))->where('id > 1'));
		var_dump((string) DbQuery::query()->replace()->from('test')->fields(array('id' => 1, 'text' => 'test'))->where('id > 1'));

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
