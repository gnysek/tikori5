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

	public function dbtestAction() {
		var_dump((string) DbQuery::sql()->from('test')->where('id > 1'));
		var_dump((string) DbQuery::sql()->from(array('test', 'another'))->where('id > 1'));
		var_dump((string) DbQuery::sql()->from(array('t' => 'test', 'aeee' => 'another'))->where(array('test', '=', 3, 'another')));
		var_dump((string) DbQuery::sql()->from('test')->where('id > 1')->joinOn('asd', array('x', '=', 'id')));
		var_dump((string) DbQuery::sql()->update()->from('test')->where('id > 1'));
		var_dump((string) DbQuery::sql()->delete()->from('test')->where('id > 1'));
		var_dump((string) DbQuery::sql()->insert()->from('test')->fields(array('1', 'test'))->where('id > 1'));
		var_dump((string) DbQuery::sql()->replace()->from('test')->fields(array('id' => 1, 'text' => 'test'))->where('id > 1'));
	}

}
