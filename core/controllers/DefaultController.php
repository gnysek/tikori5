<?php

class DefaultController extends Controller
{

    public function indexAction()
    {
        $this->render('_tikori.welcome');
    }

}
