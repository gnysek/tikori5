<?php

/**
 * @author Piotr Gnys <gnysek@gnysek.pl>
 * @package content
 */
class ContentController extends Controller {

	public function showAction($id) {
		
	}
	
	/**
	 * Displays content using static routing - example.com/&lt;path&gt;.html
	 * @param type $path Content static (seo) link to display
	 */
	public function staticAction($path) {
		// get id
		$id = 0;
		// forward action
		return $this->showAction($id);
	}

}
