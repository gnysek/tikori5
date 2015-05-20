<?php

class RequesttestController extends Controller
{
    public function indexAction() {
        var_dump($this->request->all());
    }
}
