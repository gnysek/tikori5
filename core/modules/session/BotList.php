<?php

class BotList
{

    private $_botlist
        = array(
            'Googlebot'    => 'Googlebot/2.1',
            'GoogleMobile' => 'Googlebot-Mobile/2.1',
            'GoogleRSS'    => 'feedfetcher.html',
            'MSN'          => 'msnbot/2.0',
            'Bing'         => 'bingbot/2.0',
            'Yahoo'        => 'Yahoo! Slurp/3.0',
            'Yandex'       => 'YandexBot/3.0',
            'Facebook'     => 'facebookexternalhit/1.1',
            'Facebook RSS' => 'RSSGraffiti',
            'Baidu'        => 'Baiduspider/2.0',
            'BingBot'      => 'bingbot/2.0',
        );

    public function isBot($userAgentString)
    {
        return false;
    }

    public function getBotWithName($userAgentString)
    {
        return array(false, null);
    }
}
