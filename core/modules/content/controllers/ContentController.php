<?php

/**
 * @author Piotr Gnys <gnysek@gnysek.pl>
 * @package content
 */
class ContentController extends Controller {

	public function nodeAction($id) {
		$this->render('', $id);
	}

	/**
	 * Displays content using static routing - example.com/&lt;path&gt;.html
	 * @param type $path Content static (seo) link to display
	 */
	public function staticAction($path) {

		$model = new ContentTranslation();
		$result = $model->findBy('url', $path);
//		var_dump($result);
		foreach($result as $k => $v) {
			echo $k;
			var_dump($v->getValues());
		}

		// get id
		$id = 0;
		// forward action
		return $this->nodeAction($path);
	}

}
