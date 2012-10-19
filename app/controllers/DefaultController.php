<?php

class DefaultController extends Controller {

	public function indexAction() {
		$this->render('body');
	}

	public function oneAction($id = 0) {
		echo 'one';
	}

	public function twoAction($id) {
		echo 'two';
		echo $id;
	}

}
