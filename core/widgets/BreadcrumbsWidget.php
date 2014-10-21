<?php

class BreadcrumbsWidget extends Widget
{

    public $links = array();
    public $homeLink = 'Home';
    public $homeTitle = 'Home';
    public $divider = ' &raquo; ';

    public function run()
    {
        if (empty($this->links)) {
            return;
        }

        $links = array();
        $links[] = $this->__createLink($this->homeLink, '/', $this->homeTitle);

        foreach ($this->links as $text => $url) {
            if (is_array($url)) {
                $links[] = $this->__createLink($text, $url, $text);
            } else {
                $links[] = $this->__createNolink($url);
            }
        }

        $this->__toHtml($links);
    }

    protected function __createLink($text, $url, $title)
    {
        return Html::link($text, $url, array('title' => $title));
    }

    protected function __createNolink($text)
    {
        return $text;
    }

    protected function __toHtml($links)
    {
        echo '<div class="breadcrumbs">' . implode($this->divider, $links) . '</div>';
    }

}
