<?php

/**
 * HTTP Lib
 * @author Moyo <dev@uuland.org>
 * @link https://github.com/moolex/qq.login.api
 */

if (class_exists('UULAND_QQLogin_HTTP', false)) return;

class UULAND_QQLogin_HTTP
{
	/**
	* Cache DIR (for Session)
	* @var mixed
	*/
	public $DIRCache = 'cache/';
	/**
	* User Idx (for Session)
	* @var mixed
	*/
	private $uidx = 900921;
	/**
	* Network Timeout
	* @var mixed
	*/
	public $NETTimeout = 10;
	/**
	* Browser Agent
	* @var mixed
	*/
	private $agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1130.1 Safari/536.11';
	/**
	* Iniz
	* @param mixed $DIRCache
	* @return UULAND_QQLogin_HTTP
	*/
	public function __construct($DIRCache = null, $uidx = 900921)
	{
		$DIRCache && $this->DIRCache = $DIRCache;
		if (substr($this->DIRCache, -1) != '/')
		{
		    $this->DIRCache .= '/';
		}
		$uidx && $this->uidx = $uidx;
	}
	/**
	* HTTP GET
	* @param mixed $url
	*/
	public function get($url, $params = null)
	{
		$this->packet_url_var($url, $params);
		$package = $this->packet_http('GET', $url);
		$response = $this->package_send($package['host'], $package['port'], $package['content']);
		$this->cookie_write($response['header']);
		return $response['data'];
	}
	/**
	* URL Params Pack
	* @param mixed $vars
	* @return string
	*/
	private function packet_url_var(&$url, $vars)
	{
		$ex = '';
		if ( is_string($vars) || is_numeric($vars) )
		{
			$ex = $vars;
		}
		elseif ( is_array($vars) )
		{
			$return = '';
			foreach ( $vars as $key => $val )
			{
				$return .= '&'.$key.'='.urlencode($val);
			}
			$ex = substr($return, 1);
		}
		else
		{
			$ex = '';
		}
		if ($ex)
		{
			if ( strpos($url, '?') )
			{
				$url = $url.'&'.$ex;
			}
			else
			{
				$url = $url.'?'.$ex;
			}
		}
		return $url;
	}
	/**
	* HTTP Package Create
	* @param mixed $method
	* @param mixed $url
	* @param mixed $post
	*/
	private function packet_http($method, $url, $post = null)
	{
		if (substr($url, 0, 7) != 'http://')
		{
			$url = 'http://'.$url;
		}
		$target = parse_url($url);
		isset($target['path']) || $target['path'] = '/';
		$target['url'] = $target['path'].(isset($target['query'])?('?'.$target['query']):'');
		isset($target['port']) || $target['port'] = 80;
		$host = $target['port'] == 80 ? $target['host'] : ($target['host'].':'.$target['port']);
		$cookie = $this->cookie_read($target['host']);
		$crlf = "\r\n";
		$s  = '';
		$s .= $method.' '.$target['url'].' HTTP/1.1'.$crlf;
		$s .= 'Host: '.$host.$crlf;
		$s .= 'Connection: close'.$crlf;
		$s .= 'User-Agent: '.$this->agent.$crlf;
		$s .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'.$crlf;
		$s .= 'Accept-Language: zh-CN,zh;q=0.8'.$crlf;
		$s .= 'Accept-Charset: GBK,utf-8;q=0.7,*;q=0.3'.$crlf;
		$cookie && $s .= 'Cookie: '.$cookie.$crlf;
		$s .= $crlf;
		return array(
			'host' => $target['host'],
			'port' => $target['port'],
			'content' => $s
		);
	}
	/**
	* Package SEND
	* @param mixed $host
	* @param mixed $data
	* @return mixed
	*/
	private function package_send($host, $port, $data)
	{
		$ip = gethostbyname($host);
		$sock = fsockopen($ip, $port, $err_no, $err_str, $this->NETTimeout);
		if ( !$sock ) return false;
		fwrite($sock, $data);
		$header = '';
		while ( $str = trim(fgets($sock, 4096)) )
		{
			$header .= $str."\n";
		}
		$body = '';
		while ( !feof($sock) )
		{
			$body .= fgets($sock, 4096);
		}
		fclose($sock);
		return array('header'=>$header, 'data'=>$body);
	}
	/**
	* COOKIE Write
	* @param mixed $header
	*/
	private function cookie_write($header)
	{
		preg_match_all('/Set-Cookie: (.*?)\n/i', $header, $cookies);
		$SKeys = array('EXPIRES', 'PATH', 'DOMAIN');
		foreach ($cookies[0] as $i => $__Cookie_CMD)
		{
			$CMDS = explode(';', $cookies[1][$i]);
			$cookie_ATTR = array();
			$cookie_DATA = array();
			foreach ($CMDS as $ii => $cookie_CMD)
			{
				if (trim($cookie_CMD) == '') continue;
				list($cookie_KEY, $cookie_VAL) = explode('=', $cookie_CMD);
				$cookie_KEY = trim($cookie_KEY);
				if (in_array(strtoupper($cookie_KEY), $SKeys))
				{
					$cookie_ATTR['@'.strtolower($cookie_KEY)] = $cookie_VAL;
				}
				else
				{
					$cookie_DATA = array(
						'KEY' => $cookie_KEY,
						'VAL' => $cookie_VAL
					);
				}
			}
			// DB
			$this->cookie_db($cookie_ATTR['@domain'], $cookie_DATA['KEY'], $cookie_DATA['VAL']);
		}
		$this->cookie_save();
	}
	/**
	* COOKIE Read
	* @param mixed $domain
	*/
	private function cookie_read($domain)
	{
	    $domain_LVS = explode('.', $domain);
	    $domain_LVS_COUNT = count($domain_LVS);
	    $CLV = '';
	    $cookies = array();
	    for ($i = $domain_LVS_COUNT; $i > 0; $i--)
	    {
	        $LV = $domain_LVS[$i-1];
	        $CLV = $LV.($CLV ? ('.'.$CLV) : '');
	        $cookie_db_DM = $this->cookie_db($CLV);
			$CLV_TOPS = '.'.$CLV;
			$cookie_db_TOPS = $this->cookie_db($CLV_TOPS);
			$cookie_db_ALL = array_merge($cookie_db_DM, $cookie_db_TOPS);
	        if ($cookie_db_ALL)
	        {
	            foreach ($cookie_db_ALL as $key => $val)
	            {
	                $cookies[$key] = $val;
	            }
	        }
	    }
	    $cookie_STRING = '';
	    foreach ($cookies as $key => $val)
	    {
	        $cookie_STRING .= $key.'='.$val.';';
	    }
	    return $cookie_STRING;
	}
	/**
	 * DB FILE
	 */
	private $cookie_file = null;
	/**
	 * DB DATA
	 */
	private $cookie_db = null;
	/**
	 * COOKIES DB READ / WRITE
	 * @param mixed $domain
	 * @param mixed $key
	 * @param mixed $val
	 */
	private function cookie_db($domain, $key = null, $val = null)
	{
	    if (is_null($this->cookie_file))
	    {
	        $HASH = md5($_SERVER['SERVER_NAME']);
	        $this->cookie_file = $this->DIRCache.'cookies~'.$this->uidx.'@'.$HASH.'.php';
	    }
	    if (is_null($this->cookie_db))
	    {
	        $this->cookie_db = is_file($this->cookie_file) ? include $this->cookie_file : array();
	    }
	    if (is_null($val))
	    {
	        if (is_null($key))
	        {
	            return isset($this->cookie_db[$domain]) ? $this->cookie_db[$domain] : array();
	        }
	        else
	        {
	            return isset($this->cookie_db[$domain][$key]) ? $this->cookie_db[$domain][$key] : '';
	        }
	    }
	    else
	    {
	        if (is_null($key))
	        {
	            return false;
	        }
	        elseif ($val)
	        {
	            return $this->cookie_db[$domain][$key] = $val;
	        }
			else
			{
				return false;
			}
	    }
	}
	/**
	 * Write COOKIES TO DB File
	 */
	private function cookie_save()
	{
	    if (is_null($this->cookie_file))
	    {
	        return false;
	    }
	    $php = '<?php /* ORG.UULAND.COOKIES.STORAGE <MOYO> */ return '.var_export($this->cookie_db, true).'; ?>';
	    return file_put_contents($this->cookie_file, $php);
	}
}

?>