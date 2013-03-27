<?php
/**
 * Created by JetBrains PhpStorm.
 * User: user
 * Date: 27.03.13
 * Time: 12:30
 * To change this template use File | Settings | File Templates.
 */

class Renderer
{

    public static function sex($sex = 0)
    {
        return ($sex == 0) ? 'Man' : 'Woman';
    }

    /**
     * @param int $time
     *
     * @return string Tue, 01 Jan 2013 01:23:45
     */
    public static function date_long($time = 0)
    {
        return date('D, d M Y H:i:s', $time);
    }

    /**
     * @param int $no 0 - Yes, 1 - No
     *
     * @return string
     */
    public static function yesno($no = 0)
    {
        return ($no) ? 'No' : 'Yes';
    }

    /**
     * @param int $yes 0 - No, 1 - Yes
     *
     * @return string
     */
    public static function noyes($yes = 0)
    {
        return ($yes) ? 'Yes' : 'No';
    }
}
