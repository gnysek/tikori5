<?php

class StaticController extends Controller {

	public function defaultAction() {
		if ($this->viewExists($this->params['path']) !== false) {
			$content = $this->renderPartial($this->params['path']);
		} else {
			$content = '<strong>Error 404</strong> &ndash; Requested "' . $this->params['path'] . '" content not found.';
		}

		$this->render('//static', array(
			'content' => $content
		));
	}

}