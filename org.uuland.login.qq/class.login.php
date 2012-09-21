<?php

/**
 * QQ Login
 * @author Moyo <dev@uuland.org>
 * @link https://github.com/moolex/qq.login.api
 */

class UULAND_QQLogin
{
    // User INFO
    public $appid = '8000212';
    // Server INFO
    public $DIRCache = 'cache/';
    // Locations
    private $Server = array(
    	'vfc_init' => 'http://check.ptlogin2.qq.com/check?uin={$UIN}&appid={$APPID}&ptlang=2052&r={$RANDOM}',
    	'vfc_download' => 'http://captcha.qq.com/getimage?aid={$APPID}&r={$RANDOM}&uin={$UIN}',
    	'user_login' => 'http://ptlogin2.qq.com/login?ptlang=2052&u={$UIN}&p={$PWD}&verifycode={$VFCODE}&aid={$APPID}&u1=http%3A%2F%2Fwww.qq.com%2F&ptredirect=2&h=1&from_ui=1&wording=%E5%BF%AB%E9%80%9F%E7%99%BB%E5%BD%95&mibao_css=m_ptlogin&fp=loginerroralert&action=5-36-112581&g=1&t=1&dummy='
    );
	/**
	* Iniz
	* @param mixed $DIRCache
	* @return UULAND_QQLogin
	*/
	public function __construct($DIRCache = null)
	{
		$this->DIRCache = $DIRCache ? $DIRCache : $this->DIRCache;
		substr($this->DIRCache, -1) == '/' || $this->DIRCache .= '/';
		is_dir($this->DIRCache) || mkdir($this->DIRCache);
	}
	/**
	 * QQ Login
	 */
	public function Login($uin, $password, $vfcode = null)
	{
	    if (is_null($vfcode))
	    {
	        $vfcode = $this->Captcha_check($uin);
	        if ($vfcode['need'])
	        {
	            return array(
					'ops' => 'false',
					'err' => 'VFCODE_NEED'
				);
	        }
	    }
	    $url = $this->Server['user_login'];
	    $url = str_replace(
            array('{$UIN}', '{$PWD}', '{$VFCODE}', '{$APPID}'),
            array($uin, $this->password()->Encrypt($this->UinENC($uin), $password, $vfcode), $vfcode, $this->appid),
            $url
        );
        $r = $this->http($uin)->get($url);
        preg_match("/ptuiCB\('(\d+)',\s*'(\d+)',\s*'(.*?)',\s*'(\d+)',\s*'(.*?)',\s*'(.*?)'\);/i", $r, $ms);
        // match error
        $ms || $ms = array(
        	'', '0', '0', '', '', '', ''
        );
		/**
		M/S
		4/3 登录失败，请重试!*
		4/0 您输入的验证码不正确，请重新输入。
		3/0 您输入的帐号或者密码不正确，请重新输入。
		0/0 登录成功！
		*/
		$mcode = $ms[1];
		$scode = $ms[2];
		$redirect = $ms[3];
		$_x = $ms[4];
		$message = $ms[5];
		$name = $ms[6];
		if (is_numeric($name))
		{
			// login failed
			$errmap = array(
				'0/0' => 'HTTP_ERROR',
				'4/3' => 'LOGIN_ERROR',
				'4/0' => 'VFCODE_ERROR',
				'3/0' => 'AUTH_ERROR'
			);
			$errcode = $mcode.'/'.$scode;
			return array(
				'ops' => 'false',
				'err' => isset($errmap[$errcode]) ? $errmap[$errcode] : 'ERROR'
			);
		}
		else
		{
			// login success
			return array(
				'ops' => 'true',
				'uin' => $uin,
				'name' => $name
			);
		}
	}
	private function UinENC($uin)
	{
		// TMP
		return file_get_contents($this->DIRCache.'uin.'.$uin.'.hex');
		// TMP
	}
	/**
	 * Get Captcha Image
	 */
	public function Captcha_GET($uin, $return_BIN = false)
	{
	    $vf_BIN = $this->Captcha_download($uin);
	    if ($return_BIN)
	    {
	        return $vf_BIN;
	    }
	    else
	    {
	        $vfc_file = $this->DIRCache.'vfc.'.$uin.'.jpg';
	        
	        file_put_contents($vfc_file, $vf_BIN);
	        
	        return 'http://'.$_SERVER['SERVER_NAME'].'/'.$vfc_file;
	    }
	}
	/**
	 * Captcha Iniz
	 */
    public function Captcha_check($uin)
    {
        $url = $this->Server['vfc_init'];
        $url = str_replace(
            array('{$UIN}', '{$APPID}', '{$RANDOM}'),
            array($uin, $this->appid, $this->SRandom()),
            $url
        );
        $vc_CMD = $this->http($uin)->get($url);
        preg_match('/ptui_checkVC\(\'(.*?)\',\s*\'(.*?)\',\s*\'(.*?)\'\);/i', $vc_CMD, $matchs);
        $vc_flag = (int)$matchs[1];
        $vc_data = $matchs[2];
		$vc_uin = $matchs[3];
		// TMP
		file_put_contents($this->DIRCache.'uin.'.$uin.'.hex', $vc_uin);
		// TMP
		return array(
			'need' => $vc_flag ? true : false,
			'data' => $vc_data,
			'uin' => $vc_uin
		);
    }
	/**
	 * Captcha Download
	 */
    private function Captcha_download($uin)
    {
        $url = $this->Server['vfc_download'];
        $url = str_replace(
            array('{$APPID}', '{$RANDOM}', '{$UIN}'),
            array($this->appid, $this->SRandom(), $uin),
            $url
        );
        return $this->http($uin)->get($url);
    }
	/**
	 * Random NO
	 */
    private function SRandom()
    {
        $return = '';
        for ( $i=0; $i<4; $i++ )
        {
            $return .= (string)rand(0, getrandmax());
        }
        return '0.'.$return;
    }
    /**
     * Password Handler
     */
    private $password_handler = null;
    /**
     * Password Interface
     */
    private function password()
    {
        if (is_null($this->password_handler))
        {
            $file = dirname(__FILE__) . '/class.password.php';
            is_file($file) ? include $file : exit('Missing: class.password.php');
            $this->password_handler = new UULAND_QQLogin_Password();
        }
        return $this->password_handler;
    }
    /**
     * HTTP Handler
     */
    private $http_handler = null;
    /**
     * HTTP Interface
     */
    private function http($uin)
    {
        if (is_null($this->http_handler))
        {
            $file = dirname(__FILE__) . '/class.http.php';
            is_file($file) ? include $file : exit('Missing: class.http.php');
            $this->http_handler = new UULAND_QQLogin_HTTP($this->DIRCache, $uin);
        }
        return $this->http_handler;
    }
}

?>