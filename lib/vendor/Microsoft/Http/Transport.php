<?php
/**
 * Copyright (c) 2009, RealDolmen
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of RealDolmen nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY RealDolmen ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL RealDolmen BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Microsoft
 * @package    Microsoft_Http
 * @subpackage Transport
 * @version    $Id: Transport.php 21617 2009-06-12 10:46:31Z unknown $
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

/**
 * @see Microsoft_Http_Exception
 */
require_once 'Microsoft/Http/Exception.php';

/**
 * @see Microsoft_Http_Response
 */
require_once 'Microsoft/Http/Response.php';

/**
 * @see Microsoft_Http_Transport_Curl
 */
require_once 'Microsoft/Http/Transport/Curl.php';

/**
 * @category   Microsoft
 * @package    Microsoft_Http
 * @subpackage Transport
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
abstract class Microsoft_Http_Transport
{
    /** HTTP VERBS */
    const VERB_GET      = 'GET';
    const VERB_PUT      = 'PUT';
    const VERB_POST     = 'POST';
    const VERB_DELETE   = 'DELETE';
    const VERB_HEAD     = 'HEAD';
    const VERB_MERGE    = 'MERGE';
    
	/**
	 * Use proxy?
	 * 
	 * @var boolean
	 */
	protected $_useProxy = false;
	
	/**
	 * Proxy url
	 * 
	 * @var string
	 */
	protected $_proxyUrl = '';
	
	/**
	 * Proxy port
	 * 
	 * @var int
	 */
	protected $_proxyPort = 80;
	
	/**
	 * Proxy credentials
	 * 
	 * @var string
	 */
	protected $_proxyCredentials = '';
	
	/**
	 * Set proxy
	 * 
	 * @param boolean $useProxy         Use proxy?
	 * @param string  $proxyUrl         Proxy URL
	 * @param int     $proxyPort        Proxy port
	 * @param string  $proxyCredentials Proxy credentials
	 */
	public function setProxy($useProxy = false, $proxyUrl = '', $proxyPort = 80, $proxyCredentials = '')
	{
	    $this->_useProxy = $useProxy;
	    $this->_proxyUrl = $proxyUrl;
	    $this->_proxyPort = $proxyPort;
	    $this->_proxyCredentials = $proxyCredentials;
	}
    
    /**
     * User agent string
     * 
     * @var string
     */
    protected $_userAgent = 'Microsoft_Http_Transport';
    
    /**
     * Perform GET request
     * 
     * @param $url              Url to request
     * @param $variables        Array of key-value pairs to use in the request
     * @param $headers          Array of key-value pairs to use as additional headers
     * @param $rawBody          Raw body to send to server
     * @return Microsoft_Http_Response
     */
    public function get($url, $variables = array(), $headers = array(), $rawBody = null)
    {
        return $this->request(self::VERB_GET, $url, $variables, $headers, $rawBody);
    }
    
    /**
     * Perform PUT request
     * 
     * @param $url              Url to request
     * @param $variables        Array of key-value pairs to use in the request
     * @param $headers          Array of key-value pairs to use as additional headers
     * @param $rawBody          Raw body to send to server
     * @return Microsoft_Http_Response
     */
    public function put($url, $variables = array(), $headers = array(), $rawBody = null)
    {
        return $this->request(self::VERB_PUT, $url, $variables, $headers, $rawBody);
    }
    
    /**
     * Perform POST request
     * 
     * @param $url              Url to request
     * @param $variables        Array of key-value pairs to use in the request
     * @param $headers          Array of key-value pairs to use as additional headers
     * @param $rawBody          Raw body to send to server
     * @return Microsoft_Http_Response
     */
    public function post($url, $variables = array(), $headers = array(), $rawBody = null)
    {
        return $this->request(self::VERB_POST, $url, $variables, $headers, $rawBody);
    }
    
    /**
     * Perform DELETE request
     * 
     * @param $url              Url to request
     * @param $variables        Array of key-value pairs to use in the request
     * @param $headers          Array of key-value pairs to use as additional headers
     * @param $rawBody          Raw body to send to server
     * @return Microsoft_Http_Response
     */
    public function delete($url, $variables = array(), $headers = array(), $rawBody = null)
    {
        return $this->request(self::VERB_DELETE, $url, $variables, $headers, $rawBody);
    }
    
    /**
     * Perform request
     * 
     * @param $httpVerb         Http verb to use in the request
     * @param $url              Url to request
     * @param $variables        Array of key-value pairs to use in the request
     * @param $headers          Array of key-value pairs to use as additional headers
     * @param $rawBody          Raw body to send to server
     * @return Microsoft_Http_Response
     */
    public abstract function request($httpVerb, $url, $variables = array(), $headers = array(), $rawBody = null);
    
    /**
     * Create channel
     * 
     * @param $type string   Transport channel type
     * @return Microsoft_Http_Transport
     */
    public static function createChannel($type = 'Microsoft_Http_Transport_Curl')
    {
        return new $type();
    }
}
