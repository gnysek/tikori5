<?php

class BreadcrumbsWidget extends Widget {

	public $links = array();
	public $homeLink = 'Home';

	public function run() {
		if (empty($this->links)) {
			return;
		}

		$links = array();
		$links[] = Html::link($this->homeLink, '@');

		foreach ($this->links as $k => $v) {
			if (is_array($v)) {
				$links[] = Html::link($k, $v);
			} else {
				$links[] = $v;
			}
		}

		echo '<div class="breadcrumbs">' . implode(' &raquo; ', $links) . '</div>';
	}

}
