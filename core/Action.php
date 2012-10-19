<?php

class Action {
	public $layout = 'one_column';
	public $usesSessions = true;
	public $usesFilters = true;
	
	public function filters() {
		return array(
			'*' => array('allow')
		);
	}
	
	public function beforeAction() {
		
	}
	
	public function afterAction() {
		
	}
	
	public function defaultAction() {
		echo 'akcja';
	}
	
	public function notFoundAction() {
		
	}
}
