<?php

/**
 * @author  Piotr Gnys <gnysek@gnysek.pl>
 * @package content
 */
class TContentModule extends TModule
{

    public function init()
    {
        // default routes
        Route::set(
            'content-nodes', 'content/view(/<id>)', array(
                                                         'id' => '[0-9]+'
                                                    )
        )->defaults(
                array(
                     'controller' => 'Content',
                     'action'     => 'node',
                )
            );

        Route::set(
            'content-static', '<path>.html', array(
                                                  'path' => '[a-zA-Z0-9_\-/]+',
                                             )
        )->defaults(
                array(
                     'controller' => 'Content',
                     'action'     => 'static',
                )
            );

        return true;
    }

}
