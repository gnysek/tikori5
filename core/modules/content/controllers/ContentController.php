<?php

/**
 * @author Piotr Gnys <gnysek@gnysek.pl>
 * @package content
 */
class ContentController extends Controller {

	public function nodeAction($id) {
		$result = ContentTranslation::model()->findBy('page_id', $id, true);

		$this->render('node', array('node' => $result));
	}

	/**
	 * Displays content using static routing - example.com/&lt;path&gt;.html
	 * @param type $path Content static (seo) link to display
	 */
	public function staticAction($path) {

		$result = ContentTranslation::model()->findBy('url', $path, true);
//		foreach($result as $k => $v) {
//			echo $k;
//			var_dump($v->getValues());
//		}
//		var_dump($result->getValues());
//		var_dump($result->content->getValues());
		// forward action
		$this->render('node', array('node' => $result));
//		return $this->nodeAction($path);
	}

}
