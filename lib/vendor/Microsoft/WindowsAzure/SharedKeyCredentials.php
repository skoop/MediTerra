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
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 * @version    $Id: SharedKeyCredentials.php 28585 2009-09-07 12:12:56Z unknown $
 */

/**
 * @see Microsoft_WindowsAzure_Credentials
 */
require_once 'Microsoft/WindowsAzure/Credentials.php';

/**
 * @see Microsoft_WindowsAzure_Storage
 */
require_once 'Microsoft/WindowsAzure/Storage.php';

/**
 * @see Microsoft_Http_Transport
 */
require_once 'Microsoft/Http/Transport.php';

/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */ 
class Microsoft_WindowsAzure_SharedKeyCredentials extends Microsoft_WindowsAzure_Credentials
{
    /**
	 * Sign request URL with credentials
	 *
	 * @param string $requestUrl Request URL
	 * @param string $resourceType Resource type
	 * @param string $requiredPermission Required permission
	 * @return string Signed request URL
	 */
	public function signRequestUrl($requestUrl = '', $resourceType = Microsoft_WindowsAzure_Storage::RESOURCE_UNKNOWN, $requiredPermission = Microsoft_WindowsAzure_Credentials::PERMISSION_READ)
	{
	    return $requestUrl;
	}
	
	/**
	 * Sign request headers with credentials
	 *
	 * @param string $httpVerb HTTP verb the request will use
	 * @param string $path Path for the request
	 * @param string $queryString Query string for the request
	 * @param array $headers x-ms headers to add
	 * @param boolean $forTableStorage Is the request for table storage?
	 * @param string $resourceType Resource type
	 * @param string $requiredPermission Required permission
	 * @return array Array of headers
	 */
	public function signRequestHeaders($httpVerb = Microsoft_Http_Transport::VERB_GET, $path = '/', $queryString = '', $headers = null, $forTableStorage = false, $resourceType = Microsoft_WindowsAzure_Storage::RESOURCE_UNKNOWN, $requiredPermission = Microsoft_WindowsAzure_Credentials::PERMISSION_READ)
	{
		// http://github.com/sriramk/winazurestorage/blob/214010a2f8931bac9c96dfeb337d56fe084ca63b/winazurestorage.py

		// Determine path
		if ($this->_usePathStyleUri)
			$path = substr($path, strpos($path, '/'));

		// Determine query
		$queryString = $this->prepareQueryStringForSigning($queryString);
	
		// Canonicalized headers
		$canonicalizedHeaders = array();
		
		// Request date
		$requestDate = '';
		if (isset($headers[self::PREFIX_STORAGE_HEADER . 'date']))
		{
		    $requestDate = $headers[self::PREFIX_STORAGE_HEADER . 'date'];
		}
		else 
		{
		    $requestDate = gmdate('D, d M Y H:i:s', time()) . ' GMT'; // RFC 1123
		    $canonicalizedHeaders[] = self::PREFIX_STORAGE_HEADER . 'date:' . $requestDate;
		}
		
		// Build canonicalized headers
		if (!is_null($headers))
		{
			foreach ($headers as $header => $value) {
				if (is_bool($value))
					$value = $value === true ? 'True' : 'False';

				$headers[$header] = $value;
				if (substr($header, 0, strlen(self::PREFIX_STORAGE_HEADER)) == self::PREFIX_STORAGE_HEADER)
				    $canonicalizedHeaders[] = strtolower($header) . ':' . $value;
			}
		}
		sort($canonicalizedHeaders);

		// Build canonicalized resource string
		$canonicalizedResource  = '/' . $this->_accountName;
		if ($this->_usePathStyleUri)
			$canonicalizedResource .= '/' . $this->_accountName;
		$canonicalizedResource .= $path;
		if ($queryString !== '')
		    $canonicalizedResource .= $queryString;

		// Create string to sign   
		$stringToSign = array();
		$stringToSign[] = strtoupper($httpVerb); 	// VERB
    	$stringToSign[] = "";						// Content-MD5
    	$stringToSign[] = "";						// Content-Type
    	$stringToSign[] = "";
        // Date already in $canonicalizedHeaders
    	// $stringToSign[] = self::PREFIX_STORAGE_HEADER . 'date:' . $requestDate; // Date
    	
    	if (!$forTableStorage && count($canonicalizedHeaders) > 0)
    		$stringToSign[] = implode("\n", $canonicalizedHeaders); // Canonicalized headers
    		
    	$stringToSign[] = $canonicalizedResource;		 			// Canonicalized resource
    	$stringToSign = implode("\n", $stringToSign);
    	$signString = base64_encode(hash_hmac('sha256', $stringToSign, $this->_accountKey, true));

    	// Sign request
    	$headers[self::PREFIX_STORAGE_HEADER . 'date'] = $requestDate;
    	$headers['Authorization'] = 'SharedKey ' . $this->_accountName . ':' . $signString;
    	
    	// Return headers
    	return $headers;
	}
}
