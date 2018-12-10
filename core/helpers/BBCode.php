<?php

class BBCode
{
    public static $imgClass = '';

    public static function format($text)
    {
        $text = preg_replace('#\[b\](.*?)\[/b\]#si', '<strong>$1</strong>', $text);
        $text = preg_replace('#\[u\](.*?)\[/u\]#si', '<u>$1</u>', $text);
        $text = preg_replace('#\[i\](.*?)\[/i\]#si', '<em>$1</em>', $text);
        $text = preg_replace('#\[list\](.*?)\[/list\]#si', '<ul>$1</ul>', $text);
        $text = preg_replace('#\[list=1\](.*?)\[/list\]#si', '<ol>$1</ol>', $text);
        $text = preg_replace('#\[img\](.*?)\[/img]#si', '<img src="$1" alt="Custom image" class="' . static::$imgClass . '"/>', $text);
        $text = preg_replace('#\[\*\](.*?)\[/\*\]#si', '<li>$1</li>', $text);
        $text = preg_replace(
            '#\[font size="(.*?)"\](.*?)\[/font\]#si', '<span style="font-size: $1em">$2</span>', $text
        );
        $text = preg_replace('#\[size=(.*?)\](.*?)\[/size\]#si', '<font size="$1">$2</font>', $text);
        $text = preg_replace('#\[color=(.*?)\](.*?)\[/color\]#si', '<span style="color: $1">$2</span>', $text);
        $text = preg_replace('#\[h1\](.*?)\[/h1\]#si', '<h1>$1</h1>', $text);
        $text = static::nl2p($text);
        return $text;
    }

    public static function nl2p($string)
    {
        $paragraphs = '';

        foreach (explode("\n", $string) as $line) {
            if (trim($line)) {
                $paragraphs .= '<p>' . $line . '</p>';
            }
        }

        return $paragraphs;
    }

    public static function unformat($text)
    {
        $text = preg_replace('#<strong>(.*?)</strong>#si', '[b]$1[/b]', $text);
        $text = preg_replace('#<u>(.*?)</u>#si', '[u]$1[/u]', $text);
        $text = preg_replace('#<em>(.*?)</em>#si', '[i]$1[/i]', $text);
        $text = preg_replace('#\<img src="(.*?)" alt="(.*?)" class="(.*?)"/>#si', '[img]$1[/img]', $text);
        $text = str_replace('<br/>', '\n', $text);
        return $text;
    }

}
