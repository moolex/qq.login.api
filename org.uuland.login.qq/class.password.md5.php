<?php

/**
 * MD5 Calc / FOR REMOTE
 * @author Moyo <dev@uuland.org>
 * @link https://github.com/moolex/qq.login.api
 */

error_reporting(E_ALL ^ E_NOTICE);

class UULAND_QQLogin_Password_MD5
{
    /**
     * HASH Server URL
     */
    private $HASH_Server = 'http://md5.apiz.org/';
    /**
     * Get MD5 using Normal
     */
    public function string($string)
    {
        return $this->request('string='.$string);
    }
    /**
     * Get MD5 using HEX
     */
    public function hex($hex)
    {
        return $this->request('hex='.$hex);
    }
	/**
	 * Server REQUEST
	 */
    private function request($query)
    {
        $url = $this->HASH_Server.'query?'.$query.'&case=upper&mark=yes';
        $r = $this->http()->get($url);
		preg_match('/\^([a-z0-9]+)\$/i', $r, $m);
		return $m[1];
    }
    /**
     * HTTP Handler
     */
    private $http_handler = null;
    /**
     * HTTP Interface
     */
    private function http()
    {
        if (is_null($this->http_handler))
        {
            $file = dirname(__FILE__) . '/class.http.php';
            is_file($file) ? include $file : exit('Missing: class.http.php');
            $this->http_handler = new UULAND_QQLogin_HTTP(sys_get_temp_dir());
        }
        return $this->http_handler;
    }
}

?>