<?php

class StaticController extends Controller
{

    public $defaultPath = 'none';

    /**
     * It tries to display view /views/static/<path> when /static/<path> URI requested
     * When static/<path> is empty, it tries to display views/static/$defaultPath
     * Else, it displays 404 error
     */
    public function defaultAction()
    {
        if (!empty($this->params['path']) && $this->viewExists($this->params['path']) !== false) {
            $content = $this->renderPartial($this->params['path']);
        } else {
            if (empty($this->params['path']) && $this->viewExists($this->defaultPath)) {
                $content = $this->renderPartial($this->defaultPath);
            } else {
                Core::app()->response->status(404);
                $this->httpStatusAction();
                return;
            }
        }

        $this->render(
            '//static', array(
                             'content' => $content
                        )
        );

    }

}
