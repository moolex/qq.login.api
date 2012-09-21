<?php

/**
 * QQ Login / Password
 * @author Moyo <dev@uuland.org>
 * @link https://github.com/moolex/qq.login.api
 */

error_reporting(E_ALL ^ E_NOTICE);

class UULAND_QQLogin_Password
{
    /**
     * Password Encrypt
     * @param mixed $password
     */
    public function Encrypt ($uin, $password, $vfcode)
    {
    	if (stristr($uin, '\x'))
    	{
			$uin = str_replace('\x', '', $uin);
    	}
        $A = $this->md5()->string($password);
        $K = $A.$uin;
        $B = $this->md5()->hex($K);
        $C = $this->md5()->string($B.strtoupper($vfcode));
        return $C;
    }
    /**
     * MD5 Calc Handler
     */
    private $md5_handler = null;
    /**
     * MD5 Calc
     * @param mixed $bin
     * @param mixed $size
     */
    private function md5 ()
    {
        if (is_null($this->md5_handler)) {
            $file = dirname(__FILE__) . '/class.password.md5.php';
            is_file($file) ? include $file : exit('Missing: class.password.md5.php');
            $this->md5_handler = new UULAND_QQLogin_Password_MD5();
        }
        return $this->md5_handler;
    }
}

?>