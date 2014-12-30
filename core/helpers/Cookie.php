<?php

/**
 * Description of Cookie
 *
 * @author user
 */
class Cookie extends TModule
{
    const TIME_HOUR = 3600;
    const TIME_DAY = 86400;
    const TIME_WEEK = 25200;
    const TIME_MONTH = 2592000;
    const TIME_YEAR = 31536000;

    /**
     * @var string  Sól do zabezpieczania ciastek przed hackowaniem
     */
    private $_salt = 'qwerty';

    /**
     * @var string  Domena ciastek
     */
    public $domain = null;

    /**
     * @var string  Ścieżka ciastek
     */
    public $path = '/';

    /**
     * Konstruktor klasy, pozwala zmienić defaultowe opcje
     *
     * @param       array $cfg    Opcjonalna konfiguracja ('domain', 'path', 'salt')
     */
    public function __construct(array $cfg = array())
    {
        if (!empty($cfg)) {
            if (!empty($cfg['domain'])) {
                $this->domain = $cfg['domain'];
            }
            if (!empty($cfg['path'])) {
                $this->path = $cfg['path'];
            }
            if (!empty($cfg['key'])) {
                $this->_salt = $cfg['key'];
            }
        } else {
            $this->path = '/';
            $this->domain = '.' . Core::app()->cfg('env/HOST');
        }
    }

    /**
     * Sets cookie
     *
     * @param       string  $name           Nazwa ciacha
     * @param       string  $value          Wartość ciacha
     * @param       int     $expire         Czas wygasania (0 = tylko sesja)
     * @param       boolean $secure         Czy ciastko jest ustawiane dla https://
     * @param       boolean $httponly       Czy ciastko ma być dostępne tylko dla przeglądarki, bez JS itp.
     *
     * @return      boolean
     */
    public function set($name, $value, $expire = 0, $secure = null, $httponly = null)
    {
//              setcookie($name, $this->salt($name, $value) . '#' . $value, time() + $expire, $this->path, $this->domain, $secure, $httponly);
        setcookie(
            $name, base64_encode($this->salt($name, $value) . '#' . $value), ($expire === 0) ? 0 : (time() + $expire),
            $this->path, $this->domain, $secure, $httponly
        );
        return TRUE;
    }

    /**
     * Gets cookie
     *
     * @param       string $name           Nazwa ciacha
     * @param       string $defValue       Domyślna wartość
     *
     * @return      boolean
     */
    public function get($name, $defValue = NULL)
    {
        if (!empty($_COOKIE[$name]) && strstr(base64_decode($_COOKIE[$name]), '#')) {
//                      list($oldSalt, $value) = explode('#', $_COOKIE[$name], 2);
            list($oldSalt, $value) = explode('#', base64_decode($_COOKIE[$name]), 2);
            return ($oldSalt === $this->salt($name, $value)) ? $value : $defValue;
        }

        return $defValue;
    }

    /**
     * Deletes cookie
     *
     * @param       string $name           Nazwa ciacha
     *
     * @return boolean
     */
    public function delete($name)
    {
        return $this->set($name, NULL, time() - 3600);
    }

    /**
     * Generates salt
     *
     * @param       string $name           Nazwa ciacha
     * @param       string $value          Wartość ciacha
     *
     * @return      string                          Zwraca sól SHA1
     */
    private function salt($name, $value)
    {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 'none';
        return sha1($agent . $name . $value . $this->_salt);
    }

}
