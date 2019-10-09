<?php

/**
 * Class TikoriCron
 * @see TikoriConsole
 */
abstract class TikoriCron
{
    abstract public function run($params = []);

    public function allowedParams()
    {
        return [];
    }
}
