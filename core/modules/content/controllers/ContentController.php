<?php

/**
 * @author  Piotr Gnys <gnysek@gnysek.pl>
 * @package content
 */
class ContentController extends ContentAuth
{

    public function nodeAction($id)
    {
        $result = ContentTranslation::model()->with('content')->findBy('page_id', $id, true);

        if ($result) {
            $this->render('node', array('node' => $result));
        } else {
            $this->forward404();
        }
    }

    /**
     * Displays content using static routing - example.com/&lt;path&gt;.html
     *
     * @param string $path Content static (seo) link to display
     */
    public function staticAction($path)
    {

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

    public function nodesAction($id)
    {
        $content = Content::model()->findWhere(array('parent', '=', $id), 10);

        $this->render('list', array('content' => $content));
    }

    public function indexAction()
    {
        $content = Content::model()->findWhere(array(array('parent', 'IS', null), array('type', '=', 0)), 10);
        $result = ContentTranslation::model()->findAll(10);

        $this->render('list', array('collection' => $result, 'content' => $content));
    }

}
