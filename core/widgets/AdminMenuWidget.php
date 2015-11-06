<?php

class AdminMenuWidget extends Widget
{

    public $links = array();
    public $homeLink = 'Home';

    public function onCall()
    {
        if (empty($this->links)) {
            return;
        }

        $links = array();

        foreach ($this->links as $k => $v) {
            $links[] = Html::htmlTag(
                'li', array(
                           'class' => (
                               trim(str_replace('admin/', '', Core::app()->request->getRouterPath()), '/') == $v[0])
                               ? 'active'
                               : ''),
                Html::link(
                    '', array($v[0]), array(
                                           'class' => $v[1],
                                           'title' => $v[2]
                                      )
                )
            );
        }

        echo '<ul class="unstyled">' . implode(PHP_EOL, $links) . '</ul>';
    }
}
