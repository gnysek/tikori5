<?php

class DefaultController extends Controller {
	
//	public function defaultAction() {
//		
//	}

	public function indexAction() {
		$model = Team::model()->find(1);
		/* @var $model Team */
		$this->render('body', array(
			'test1' => $model->users,
			'test2' => $model->users[0]->team0
		));
	}

	public function configtestAction($id = 0) {
		var_dump(Core::app()->cfg('test'));
		var_dump(Core::app()->cfg('test2/x/y'));
		echo '<h1>test</h1>';
		var_dump(Core::app()->cfg()->set('test', 12));
		var_dump(Core::app()->cfg()->set('test2/x/y', 15));
		var_dump(Core::app()->cfg()->set('test2/x/y', 17));
		var_dump(Core::app()->cfg()->set('test2/x/z', 19));
		var_dump(Core::app()->cfg()->set('test2/e', 'b'));
		echo '<h1>after</h1>';
		var_dump(Core::app()->cfg('test'));
		var_dump(Core::app()->cfg('test2/x/y'));
		echo '<h1>result</h1>';
		var_dump(Core::app()->cfg('test2*'));
		var_dump(Core::app()->flatcfg('test2'));
		echo '<h1>whole cfg</h1>';
		var_dump(Core::app()->cfg(''));
		var_dump(Core::app()->flatcfg(''));
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
