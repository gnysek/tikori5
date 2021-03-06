<?php

/**
 * Session model
 *
 * @property string $sid
 * @property User   $user
 * @property int    $user_id
 * @property int    $logged_in
 * @property int    $start_time
 * @property int    $current_time
 * @property string $ip
 * @property string $page
 * @property string $browser
 * @property string $data
 */
class Session extends TModel
{

    protected $_table = 'sessions';
    protected $_primaryKey = 'sid';

    /**
     * @param string|null $model
     *
     * @return Session|TModel
     */
    public static function model($model = null)
    {
        return parent::model($model);
    }

    public function beforeSave()
    {
        $this->current_time = time();

        if ($this->_isNewRecord) {
            if ($this->user_id == null) {
                $this->user_id = -1;
            }
            if ($this->logged_in == null) {
                $this->logged_in = 0;
            }
            $this->start_time = $this->current_time;
            $this->ip = Core::app()->request->get('ip');
        }

        return true;
    }
}
