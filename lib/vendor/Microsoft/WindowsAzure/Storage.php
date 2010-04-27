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
 * @package    Microsoft_WindowsAzure
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 * @version    $Id: Storage.php 28585 2009-09-07 12:12:56Z unknown $
 */

/**
 * @see Microsoft_WindowsAzure_Credentials
 */
require_once 'Microsoft/WindowsAzure/Credentials.php';

/**
 * @see Microsoft_WindowsAzure_SharedKeyCredentials
 */
require_once 'Microsoft/WindowsAzure/SharedKeyCredentials.php';

/**
 * @see Microsoft_WindowsAzure_RetryPolicy
 */
require_once 'Microsoft/WindowsAzure/RetryPolicy.php';

/**
 * @see Microsoft_WindowsAzure_Exception
 */
require_once 'Microsoft/WindowsAzure/Exception.php';

/**
 * @see Microsoft_Http_Transport
 */
require_once 'Microsoft/Http/Transport.php';

/**
 * @see Microsoft_Http_Response
 */
require_once 'Microsoft/Http/Response.php';

/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_WindowsAzure_Storage
{
	/**
	 * Development storage URLS
	 */
	const URL_DEV_BLOB      = "127.0.0.1:10000";
	const URL_DEV_QUEUE     = "127.0.0.1:10001";
	const URL_DEV_TABLE     = "127.0.0.1:10002";
	
	/**
	 * Live storage URLS
	 */
	const URL_CLOUD_BLOB    = "blob.core.windows.net";
	const URL_CLOUD_QUEUE   = "queue.core.windows.net";
	const URL_CLOUD_TABLE   = "table.core.windows.net";
	
	/**
	 * Resource types
	 */
	const RESOURCE_UNKNOWN     = "unknown";
	const RESOURCE_CONTAINER   = "c";
	const RESOURCE_BLOB        = "b";
	const RESOURCE_TABLE       = "t";
	const RESOURCE_ENTITY      = "e";
	const RESOURCE_QUEUE       = "q";
	
	/**
	 * Current API version
	 * 
	 * @var string
	 */
	protected $_apiVersion = '2009-04-14';
	
	/**
	 * Storage host name
	 *
	 * @var string
	 */
	protected $_host = '';
	
	/**
	 * Account name for Windows Azure
	 *
	 * @var string
	 */
	protected $_accountName = '';
	
	/**
	 * Account key for Windows Azure
	 *
	 * @var string
	 */
	protected $_accountKey = '';
	
	/**
	 * Use path-style URI's
	 *
	 * @var boolean
	 */
	protected $_usePathStyleUri = false;
	
	/**
	 * Microsoft_WindowsAzure_Credentials instance
	 *
	 * @var Microsoft_WindowsAzure_Credentials
	 */
	protected $_credentials = null;
	
	/**
	 * Microsoft_WindowsAzure_RetryPolicy instance
	 * 
	 * @var Microsoft_WindowsAzure_RetryPolicy
	 */
	protected $_retryPolicy = null;
	
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
	 * Creates a new Microsoft_WindowsAzure_Storage instance
	 *
	 * @param string $host Storage host name
	 * @param string $accountName Account name for Windows Azure
	 * @param string $accountKey Account key for Windows Azure
	 * @param boolean $usePathStyleUri Use path-style URI's
	 * @param Microsoft_WindowsAzure_RetryPolicy $retryPolicy Retry policy to use when making requests
	 */
	public function __construct($host = self::URL_DEV_BLOB, $accountName = Microsoft_WindowsAzure_Credentials::DEVSTORE_ACCOUNT, $accountKey = Microsoft_WindowsAzure_Credentials::DEVSTORE_KEY, $usePathStyleUri = false, Microsoft_WindowsAzure_RetryPolicy $retryPolicy = null)
	{
		$this->_host = $host;
		$this->_accountName = $accountName;
		$this->_accountKey = $accountKey;
		$this->_usePathStyleUri = $usePathStyleUri;
		
		// Using local storage?
		if (!$this->_usePathStyleUri && ($this->_host == self::URL_DEV_BLOB || $this->_host == self::URL_DEV_QUEUE || $this->_host == self::URL_DEV_TABLE)) // Local storage
			$this->_usePathStyleUri = true;
		
		if (is_null($this->_credentials))
		    $this->_credentials = new Microsoft_WindowsAzure_SharedKeyCredentials($this->_accountName, $this->_accountKey, $this->_usePathStyleUri);
		
		$this->_retryPolicy = $retryPolicy;
		if (is_null($this->_retryPolicy))
		    $this->_retryPolicy = Microsoft_WindowsAzure_RetryPolicy::noRetry();
	}
	
	/**
	 * Set retry policy to use when making requests
	 *
	 * @param Microsoft_WindowsAzure_RetryPolicy $retryPolicy Retry policy to use when making requests
	 */
	public function setRetryPolicy(Microsoft_WindowsAzure_RetryPolicy $retryPolicy = null)
	{
		$this->_retryPolicy = $retryPolicy;
		if (is_null($this->_retryPolicy))
		    $this->_retryPolicy = Microsoft_WindowsAzure_RetryPolicy::noRetry();
	}
	
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
	 * Returns the Windows Azure account name
	 * 
	 * @return string
	 */
	public function getAccountName()
	{
		return $this->_accountName;
	}
	
	/**
	 * Get base URL for creating requests
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		if ($this->_usePathStyleUri)
			return 'http://' . $this->_host . '/' . $this->_accountName;
		else
			return 'http://' . $this->_accountName . '.' . $this->_host;
	}
	
	/**
	 * Set Microsoft_WindowsAzure_Credentials instance
	 * 
	 * @param Microsoft_WindowsAzure_Credentials $credentials Microsoft_WindowsAzure_Credentials instance to use for request signing.
	 */
	public function setCredentials(Microsoft_WindowsAzure_Credentials $credentials)
	{
	    $this->_credentials = $credentials;
	    $this->_credentials->setAccountName($this->_accountName);
	    $this->_credentials->setAccountkey($this->_accountKey);
	    $this->_credentials->setUsePathStyleUri($this->_usePathStyleUri);
	}
	
	/**
	 * Get Microsoft_WindowsAzure_Credentials instance
	 * 
	 * @return Microsoft_WindowsAzure_Credentials
	 */
	public function getCredentials()
	{
	    return $this->_credentials;
	}
	
	/**
	 * Perform request using Microsoft_Http_Transport channel
	 *
	 * @param string $path Path
	 * @param string $queryString Query string
	 * @param string $httpVerb HTTP verb the request will use
	 * @param array $headers x-ms headers to add
	 * @param boolean $forTableStorage Is the request for table storage?
	 * @param mixed $rawData Optional RAW HTTP data to be sent over the wire
	 * @param string $resourceType Resource type
	 * @param string $requiredPermission Required permission
	 * @return Microsoft_Http_Response
	 */
	protected function performRequest($path = '/', $queryString = '', $httpVerb = Microsoft_Http_Transport::VERB_GET, $headers = array(), $forTableStorage = false, $rawData = null, $resourceType = Microsoft_WindowsAzure_Storage::RESOURCE_UNKNOWN, $requiredPermission = Microsoft_WindowsAzure_Credentials::PERMISSION_READ)
	{
	    // Clean path
		if (strpos($path, '/') !== 0) 
			$path = '/' . $path;
			
		// Clean headers
		if (is_null($headers))
		    $headers = array();
		    
		// Add version header
		$headers['x-ms-version'] = $this->_apiVersion;
		    
		// URL encoding
		$path           = self::urlencode($path);
		$queryString    = self::urlencode($queryString);

		// Generate URL and sign request
		$requestUrl     = $this->_credentials->signRequestUrl($this->getBaseUrl() . $path . $queryString, $resourceType, $requiredPermission);
		$requestHeaders = $this->_credentials->signRequestHeaders($httpVerb, $path, $queryString, $headers, $forTableStorage, $resourceType, $requiredPermission);

		$requestClient  = Microsoft_Http_Transport::createChannel();
		if ($this->_useProxy)
		{
		    $requestClient->setProxy($this->_useProxy, $this->_proxyUrl, $this->_proxyPort, $this->_proxyCredentials);
		}
		$response = $this->_retryPolicy->execute(
		    array($requestClient, 'request'),
		    array($httpVerb, $requestUrl, array(), $requestHeaders, $rawData)
		);
		
		$requestClient = null;
		unset($requestClient);
		
		return $response;
	}
	
	/** 
	 * Parse result from Microsoft_Http_Response
	 *
	 * @param Microsoft_Http_Response $response Response from HTTP call
	 * @return object
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	protected function parseResponse(Microsoft_Http_Response $response = null)
	{
		if (is_null($response))
			throw new Microsoft_WindowsAzure_Exception('Response should not be null.');
		
        $xml = @simplexml_load_string($response->getBody());
        
        if ($xml !== false)
        {
            // Fetch all namespaces 
            $namespaces = array_merge($xml->getNamespaces(true), $xml->getDocNamespaces(true)); 
            
            // Register all namespace prefixes
            foreach ($namespaces as $prefix => $ns) { 
                if ($prefix != '')
                    $xml->registerXPathNamespace($prefix, $ns); 
            } 
        }
        
        return $xml;
	}
	
	/**
	 * Generate ISO 8601 compliant date string in UTC time zone
	 * 
	 * @param int $timestamp
	 * @return string
	 */
	public function isoDate($timestamp = null) 
	{        
	    $tz = @date_default_timezone_get();
	    @date_default_timezone_set('UTC');
	    
	    if (is_null($timestamp))
	        $timestamp = time();
	        
	    $returnValue = str_replace('+00:00', '.0000000Z', @date('c', $timestamp));
	    @date_default_timezone_set($tz);
	    return $returnValue;
	}
	
	/**
	 * URL encode function
	 * 
	 * @param  string $value Value to encode
	 * @return string        Encoded value
	 */
	public static function urlencode($value)
	{
	    return str_replace(' ', '%20', $value);
	}
}
