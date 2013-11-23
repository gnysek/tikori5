<?php

class BreadcrumbsWidget extends Widget
{

    public $links = array();
    public $homeLink = 'Home';
    public $homeTitle = 'Home';

    public function run()
    {
        if (empty($this->links)) {
            return;
        }

        $links = array();
        $links[] = Html::link($this->homeLink, '/', array('title' => $this->homeTitle));

        foreach ($this->links as $text => $url) {
            if (is_array($url)) {
                $links[] = Html::link($text, $url, array('title' => $text));
            } else {
                $links[] = $url;
            }
        }

        echo '<div class="breadcrumbs">' . implode(' &raquo; ', $links) . '</div>';
    }

}
