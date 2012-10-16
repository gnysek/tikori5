<?php

class DefaultController extends Controller {

	public function indexAction() {
		echo 'xxx';
	}

	public function oneAction($id = 0) {
		echo 'one';
	}

	public function twoAction($id) {
		echo 'two';
		echo $id;
	}

}
