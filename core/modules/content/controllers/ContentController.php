<?php

/**
 * @author  Piotr Gnys <gnysek@gnysek.pl>
 * @package content
 */
class ContentController extends ContentAuth
{

    public function nodeAction($id)
    {
        $result = ContentTranslation::model()->findBy('page_id', $id, true);

        $this->render('node', array('node' => $result));
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

    public function defaultAction()
    {
        $content = Content::model()->findWhere(array('parent', 'IS', null), 10);
        $result = ContentTranslation::model()->findAll(10);

        $this->render('list', array('collection' => $result, 'content' => $content));
    }

}
