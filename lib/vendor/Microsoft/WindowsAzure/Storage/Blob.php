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
 * @license    http://todo     name_todo
 * @version    $Id: Blob.php 33934 2009-11-03 07:21:49Z unknown $
 */

/**
 * @see Microsoft_WindowsAzure_SharedKeyCredentials
 */
require_once 'Microsoft/WindowsAzure/SharedKeyCredentials.php';

/**
 * @see Microsoft_WindowsAzure_SharedAccessSignatureCredentials
 */
require_once 'Microsoft/WindowsAzure/SharedAccessSignatureCredentials.php';

/**
 * @see Microsoft_WindowsAzure_RetryPolicy
 */
require_once 'Microsoft/WindowsAzure/RetryPolicy.php';

/**
 * @see Microsoft_Http_Transport
 */
require_once 'Microsoft/Http/Transport.php';

/**
 * @see Microsoft_Http_Response
 */
require_once 'Microsoft/Http/Response.php';

/**
 * @see Microsoft_WindowsAzure_Storage
 */
require_once 'Microsoft/WindowsAzure/Storage.php';

/**
 * @see Microsoft_WindowsAzure_Storage_BlobContainer
 */
require_once 'Microsoft/WindowsAzure/Storage/BlobContainer.php';

/**
 * @see Microsoft_WindowsAzure_Storage_BlobInstance
 */
require_once 'Microsoft/WindowsAzure/Storage/BlobInstance.php';

/**
 * @see Microsoft_WindowsAzure_Storage_SignedIdentifier
 */
require_once 'Microsoft/WindowsAzure/Storage/SignedIdentifier.php';

/**
 * @see Microsoft_WindowsAzure_Exception
 */
require_once 'Microsoft/WindowsAzure/Exception.php';


/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Microsoft_WindowsAzure_Storage_Blob extends Microsoft_WindowsAzure_Storage
{
	/**
	 * ACL - Private access
	 */
	const ACL_PRIVATE = false;
	
	/**
	 * ACL - Public access
	 */
	const ACL_PUBLIC = true;
	
	/**
	 * Maximal blob size (in bytes)
	 */
	const MAX_BLOB_SIZE = 67108864;

	/**
	 * Maximal blob transfer size (in bytes)
	 */
	const MAX_BLOB_TRANSFER_SIZE = 4194304;
	
    /**
     * Stream wrapper clients
     *
     * @var array
     */
    protected static $_wrapperClients = array();
    
    /**
     * SharedAccessSignature credentials
     * 
     * @var Microsoft_WindowsAzure_SharedAccessSignatureCredentials
     */
    private $_sharedAccessSignatureCredentials = null;
	
	/**
	 * Creates a new Microsoft_WindowsAzure_Storage_Blob instance
	 *
	 * @param string $host Storage host name
	 * @param string $accountName Account name for Windows Azure
	 * @param string $accountKey Account key for Windows Azure
	 * @param boolean $usePathStyleUri Use path-style URI's
	 * @param Microsoft_WindowsAzure_RetryPolicy $retryPolicy Retry policy to use when making requests
	 */
	public function __construct($host = Microsoft_WindowsAzure_Storage::URL_DEV_BLOB, $accountName = Microsoft_WindowsAzure_SharedKeyCredentials::DEVSTORE_ACCOUNT, $accountKey = Microsoft_WindowsAzure_SharedKeyCredentials::DEVSTORE_KEY, $usePathStyleUri = false, Microsoft_WindowsAzure_RetryPolicy $retryPolicy = null)
	{
		parent::__construct($host, $accountName, $accountKey, $usePathStyleUri, $retryPolicy);
		
		// API version
		$this->_apiVersion = '2009-07-17';
		
		// SharedAccessSignature credentials
		$this->_sharedAccessSignatureCredentials = new Microsoft_WindowsAzure_SharedAccessSignatureCredentials($accountName, $accountKey, $usePathStyleUri);
	}
	
	/**
	 * Check if a blob exists
	 * 
	 * @param string $containerName Container name
	 * @param string $blobName      Blob name
	 * @return boolean
	 */
	public function blobExists($containerName = '', $blobName = '')
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		
		// List blobs
        $blobs = $this->listBlobs($containerName, $blobName, '', 1);
        foreach ($blobs as $blob)
        {
            if ($blob->Name == $blobName)
                return true;
        }
        
        return false;
	}
	
	/**
	 * Check if a container exists
	 * 
	 * @param string $containerName Container name
	 * @return boolean
	 */
	public function containerExists($containerName = '')
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
			
		// List containers
        $containers = $this->listContainers($containerName, 1);
        foreach ($containers as $container)
        {
            if ($container->Name == $containerName)
                return true;
        }
        
        return false;
	}
	
	/**
	 * Create container
	 *
	 * @param string $containerName Container name
	 * @param array  $metadata      Key/value pairs of meta data
	 * @return object Container properties
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function createContainer($containerName = '', $metadata = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if (!is_array($metadata))
			throw new Microsoft_WindowsAzure_Exception('Meta data should be an array of key and value pairs.');
			
		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($containerName, '?restype=container', Microsoft_Http_Transport::VERB_PUT, $headers, false, null, Microsoft_WindowsAzure_Storage::RESOURCE_CONTAINER, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);			
		if ($response->isSuccessful())
		{
		    return new Microsoft_WindowsAzure_Storage_BlobContainer(
		        $containerName,
		        $response->getHeader('Etag'),
		        $response->getHeader('Last-modified'),
		        $metadata
		    );
		}
		else
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Get container ACL
	 *
	 * @param string $containerName Container name
	 * @param bool   $signedIdentifiers Display only public/private or display signed identifiers?
	 * @return bool Acl, to be compared with Microsoft_WindowsAzure_Storage_Blob::ACL_*
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function getContainerAcl($containerName = '', $signedIdentifiers = false)
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');

		// Perform request
		$response = $this->performRequest($containerName, '?restype=container&comp=acl', Microsoft_Http_Transport::VERB_GET, array(), false, null, Microsoft_WindowsAzure_Storage::RESOURCE_CONTAINER, Microsoft_WindowsAzure_Credentials::PERMISSION_READ);
		if ($response->isSuccessful())
		{
		    if ($signedIdentifiers == false)
		    {
		        // Only public/private
			    return $response->getHeader('x-ms-prop-publicaccess') == 'True';
		    }
		    else
		    {
       		    // Parse result
    		    $result = $this->parseResponse($response);
    		    if (!$result)
    		        return array();
    
    		    $entries = null;
    		    if ($result->SignedIdentifier)
    		    {
        		    if (count($result->SignedIdentifier) > 1)
        		    {
        		        $entries = $result->SignedIdentifier;
        		    }
        		    else
        		    {
        		        $entries = array($result->SignedIdentifier);
        		    }
    		    }
    		    
    		    // Return value
    		    $returnValue = array();
    		    foreach ($entries as $entry)
    		    {
    		        $returnValue[] = new Microsoft_WindowsAzure_Storage_SignedIdentifier(
    		            $entry->Id,
    		            $entry->AccessPolicy ? $entry->AccessPolicy->Start ? $entry->AccessPolicy->Start : '' : '',
    		            $entry->AccessPolicy ? $entry->AccessPolicy->Expiry ? $entry->AccessPolicy->Expiry : '' : '',
    		            $entry->AccessPolicy ? $entry->AccessPolicy->Permission ? $entry->AccessPolicy->Permission : '' : ''
    		        );
    		    }
    		    
    		    // Return
    		    return $returnValue;
		    }			   
		}
		else
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Set container ACL
	 *
	 * @param string $containerName Container name
	 * @param bool $acl Microsoft_WindowsAzure_Storage_Blob::ACL_*
	 * @param array $signedIdentifiers Signed identifiers
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function setContainerAcl($containerName = '', $acl = self::ACL_PRIVATE, $signedIdentifiers = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');

		// Policies
		$policies = null;
		if (is_array($signedIdentifiers) && count($signedIdentifiers) > 0)
		{
		    $policies  = '';
		    $policies .= '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
		    $policies .= '<SignedIdentifiers>' . "\r\n";
		    foreach ($signedIdentifiers as $signedIdentifier)
		    {
		        $policies .= '  <SignedIdentifier>' . "\r\n";
		        $policies .= '    <Id>' . $signedIdentifier->Id . '</Id>' . "\r\n";
		        $policies .= '    <AccessPolicy>' . "\r\n";
		        if ($signedIdentifier->Start != '')
		            $policies .= '      <Start>' . $signedIdentifier->Start . '</Start>' . "\r\n";
		        if ($signedIdentifier->Expiry != '')
		            $policies .= '      <Expiry>' . $signedIdentifier->Expiry . '</Expiry>' . "\r\n";
		        if ($signedIdentifier->Permissions != '')
		            $policies .= '      <Permission>' . $signedIdentifier->Permissions . '</Permission>' . "\r\n";
		        $policies .= '    </AccessPolicy>' . "\r\n";
		        $policies .= '  </SignedIdentifier>' . "\r\n";
		    }
		    $policies .= '</SignedIdentifiers>' . "\r\n";
		}
		
		// Perform request
		$response = $this->performRequest($containerName, '?restype=container&comp=acl', Microsoft_Http_Transport::VERB_PUT, array('x-ms-prop-publicaccess' => $acl), false, $policies, Microsoft_WindowsAzure_Storage::RESOURCE_CONTAINER, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);
		if (!$response->isSuccessful())
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Get container
	 * 
	 * @param string $containerName  Container name
	 * @return Microsoft_WindowsAzure_Storage_BlobContainer
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function getContainer($containerName = '')
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		    
		// Perform request
		$response = $this->performRequest($containerName, '?restype=container', Microsoft_Http_Transport::VERB_GET, array(), false, null, Microsoft_WindowsAzure_Storage::RESOURCE_CONTAINER, Microsoft_WindowsAzure_Credentials::PERMISSION_READ);	
		if ($response->isSuccessful())
		{
		    // Parse metadata
		    $metadata = array();
		    foreach ($response->getHeaders() as $key => $value)
		    {
		        if (substr(strtolower($key), 0, 10) == "x-ms-meta-")
		        {
		            $metadata[str_replace("x-ms-meta-", '', strtolower($key))] = $value;
		        }
		    }

		    // Return container
		    return new Microsoft_WindowsAzure_Storage_BlobContainer(
		        $containerName,
		        $response->getHeader('Etag'),
		        $response->getHeader('Last-modified'),
		        $metadata
		    );
		}
		else
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Get container metadata
	 * 
	 * @param string $containerName  Container name
	 * @return array Key/value pairs of meta data
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function getContainerMetadata($containerName = '')
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		
	    return $this->getContainer($containerName)->Metadata;
	}
	
	/**
	 * Set container metadata
	 * 
	 * Calling the Set Container Metadata operation overwrites all existing metadata that is associated with the container. It's not possible to modify an individual name/value pair.
	 *
	 * @param string $containerName      Container name
	 * @param array  $metadata           Key/value pairs of meta data
	 * @param array  $additionalHeaders  Additional headers. See http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function setContainerMetadata($containerName = '', $metadata = array(), $additionalHeaders = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if (!is_array($metadata))
			throw new Microsoft_WindowsAzure_Exception('Meta data should be an array of key and value pairs.');
		if (count($metadata) == 0)
		    return;
		    
		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Additional headers?
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($containerName, '?restype=container&comp=metadata', Microsoft_Http_Transport::VERB_PUT, $headers, false, null, Microsoft_WindowsAzure_Storage::RESOURCE_CONTAINER, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);
		if (!$response->isSuccessful())
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Delete container
	 *
	 * @param string $containerName      Container name
	 * @param array  $additionalHeaders  Additional headers. See http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function deleteContainer($containerName = '', $additionalHeaders = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
			
		// Additional headers?
		$headers = array();
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($containerName, '?restype=container', Microsoft_Http_Transport::VERB_DELETE, $headers, false, null, Microsoft_WindowsAzure_Storage::RESOURCE_CONTAINER, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);
		if (!$response->isSuccessful())
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * List containers
	 *
	 * @param string $prefix     Optional. Filters the results to return only containers whose name begins with the specified prefix.
	 * @param int    $maxResults Optional. Specifies the maximum number of containers to return per call to Azure storage. This does NOT affect list size returned by this function. (maximum: 5000)
	 * @param string $marker     Optional string value that identifies the portion of the list to be returned with the next list operation.
	 * @param int    $currentResultCount Current result count (internal use)
	 * @return array
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function listContainers($prefix = null, $maxResults = null, $marker = null, $currentResultCount = 0)
	{
	    // Build query string
	    $queryString = '?comp=list';
	    if (!is_null($prefix))
	        $queryString .= '&prefix=' . $prefix;
	    if (!is_null($maxResults))
	        $queryString .= '&maxresults=' . $maxResults;
	    if (!is_null($marker))
	        $queryString .= '&marker=' . $marker;
	        
		// Perform request
		$response = $this->performRequest('', $queryString, Microsoft_Http_Transport::VERB_GET, array(), false, null, Microsoft_WindowsAzure_Storage::RESOURCE_CONTAINER, Microsoft_WindowsAzure_Credentials::PERMISSION_LIST);	
		if ($response->isSuccessful())
		{
			$xmlContainers = $this->parseResponse($response)->Containers->Container;
			$xmlMarker = (string)$this->parseResponse($response)->NextMarker;

			$containers = array();
			if (!is_null($xmlContainers))
			{
				for ($i = 0; $i < count($xmlContainers); $i++)
				{
					$containers[] = new Microsoft_WindowsAzure_Storage_BlobContainer(
						(string)$xmlContainers[$i]->Name,
						(string)$xmlContainers[$i]->Etag,
						(string)$xmlContainers[$i]->LastModified
					);
				}
			}
			$currentResultCount = $currentResultCount + count($containers);
			if (!is_null($maxResults) && $currentResultCount < $maxResults)
			{
    			if (!is_null($xmlMarker) && $xmlMarker != '')
    			{
    			    $containers = array_merge($containers, $this->listContainers($prefix, $maxResults, $xmlMarker, $currentResultCount));
    			}
			}
			if (!is_null($maxResults) && count($containers) > $maxResults)
			    $containers = array_slice($containers, 0, $maxResults);
			    
			return $containers;
		}
		else 
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Put blob
	 *
	 * @param string $containerName      Container name
	 * @param string $blobName           Blob name
	 * @param string $localFileName      Local file name to be uploaded
	 * @param array  $metadata           Key/value pairs of meta data
	 * @param array  $additionalHeaders  Additional headers. See http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 * @return object Partial blob properties
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function putBlob($containerName = '', $blobName = '', $localFileName = '', $metadata = array(), $additionalHeaders = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if ($localFileName === '')
			throw new Microsoft_WindowsAzure_Exception('Local file name is not specified.');
		if (!file_exists($localFileName))
			throw new Microsoft_WindowsAzure_Exception('Local file not found.');
		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
			
		// Check file size
		if (filesize($localFileName) >= self::MAX_BLOB_SIZE)
			return $this->putLargeBlob($containerName, $blobName, $localFileName, $metadata);

		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Additional headers?
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}
		
		// File contents
		$fileContents = file_get_contents($localFileName);
		
		// Resource name
		$resourceName = self::createResourceName($containerName , $blobName);
		
		// Perform request
		$response = $this->performRequest($resourceName, '', Microsoft_Http_Transport::VERB_PUT, $headers, false, $fileContents, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);	
		if ($response->isSuccessful())
		{
			return new Microsoft_WindowsAzure_Storage_BlobInstance(
				$containerName,
				$blobName,
				$response->getHeader('Etag'),
				$response->getHeader('Last-modified'),
				$this->getBaseUrl() . '/' . $containerName . '/' . $blobName,
				strlen($fileContents),
				'',
				'',
				'',
				false,
		        $metadata
			);
		}
		else
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Put large blob (> 64 MB)
	 *
	 * @param string $containerName Container name
	 * @param string $blobName Blob name
	 * @param string $localFileName Local file name to be uploaded
	 * @param array  $metadata      Key/value pairs of meta data
	 * @return object Partial blob properties
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function putLargeBlob($containerName = '', $blobName = '', $localFileName = '', $metadata = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if ($localFileName === '')
			throw new Microsoft_WindowsAzure_Exception('Local file name is not specified.');
		if (!file_exists($localFileName))
			throw new Microsoft_WindowsAzure_Exception('Local file not found.');
		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
			
		// Check file size
		if (filesize($localFileName) < self::MAX_BLOB_SIZE)
			return $this->putBlob($containerName, $blobName, $localFileName, $metadata);
			
		// Determine number of parts
		$numberOfParts = ceil( filesize($localFileName) / self::MAX_BLOB_TRANSFER_SIZE );
		
		// Generate block id's
		$blockIdentifiers = array();
		for ($i = 0; $i < $numberOfParts; $i++)
		{
			$blockIdentifiers[] = $this->generateBlockId($i);
		}
		
		// Open file
		$fp = fopen($localFileName, 'r');
		if ($fp === false)
			throw new Microsoft_WindowsAzure_Exception('Could not open local file.');
			
		// Upload parts
		for ($i = 0; $i < $numberOfParts; $i++)
		{
			// Seek position in file
			fseek($fp, $i * self::MAX_BLOB_TRANSFER_SIZE);
			
			// Read contents
			$fileContents = fread($fp, self::MAX_BLOB_TRANSFER_SIZE);
			
			// Put block
			$this->putBlock($containerName, $blobName, $blockIdentifiers[$i], $fileContents);
			
			// Dispose file contents
			$fileContents = null;
			unset($fileContents);
		}
		
		// Close file
		fclose($fp);
		
		// Put block list
		$this->putBlockList($containerName, $blobName, $blockIdentifiers, $metadata);
		
		// Return information of the blob
		return $this->getBlobInstance($containerName, $blobName);
	}			
			
	/**
	 * Put large blob block
	 *
	 * @param string $containerName Container name
	 * @param string $blobName      Blob name
	 * @param string $identifier    Block ID
	 * @param array  $contents      Contents of the block
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function putBlock($containerName = '', $blobName = '', $identifier = '', $contents = '')
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($identifier === '')
			throw new Microsoft_WindowsAzure_Exception('Block identifier is not specified.');
		if (strlen($contents) > self::MAX_BLOB_TRANSFER_SIZE)
			throw new Microsoft_WindowsAzure_Exception('Block size is too big.');
		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
			
		// Resource name
		$resourceName = self::createResourceName($containerName , $blobName);
		
    	// Upload
		$response = $this->performRequest($resourceName, '?comp=block&blockid=' . base64_encode($identifier), Microsoft_Http_Transport::VERB_PUT, null, false, $contents, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);
		if (!$response->isSuccessful())
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Put block list
	 *
	 * @param string $containerName      Container name
	 * @param string $blobName           Blob name
	 * @param array $blockList           Array of block identifiers
	 * @param array  $metadata           Key/value pairs of meta data
	 * @param array  $additionalHeaders  Additional headers. See http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function putBlockList($containerName = '', $blobName = '', $blockList = array(), $metadata = array(), $additionalHeaders = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if (count($blockList) == 0)
			throw new Microsoft_WindowsAzure_Exception('Block list does not contain any elements.');
		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
		
		// Generate block list
		$blocks = '';
		foreach ($blockList as $block)
		{
			$blocks .= '  <Latest>' . base64_encode($block) . '</Latest>' . "\n";
		}
		
		// Generate block list request
		$fileContents = utf8_encode(implode("\n", array(
			'<?xml version="1.0" encoding="utf-8"?>',
			'<BlockList>',
			$blocks,
			'</BlockList>'
		)));
		
	    // Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Additional headers?
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}

		// Resource name
		$resourceName = self::createResourceName($containerName , $blobName);
		
		// Perform request
		$response = $this->performRequest($resourceName, '?comp=blocklist', Microsoft_Http_Transport::VERB_PUT, $headers, false, $fileContents, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);
		if (!$response->isSuccessful())
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Get block list
	 *
	 * @param string $containerName Container name
	 * @param string $blobName      Blob name
	 * @param integer $type         Type of block list to retrieve. 0 = all, 1 = committed, 2 = uncommitted
	 * @return array
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function getBlockList($containerName = '', $blobName = '', $type = 0)
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if ($type < 0 || $type > 2)
			throw new Microsoft_WindowsAzure_Exception('Invalid type of block list to retrieve.');
			
		// Set $blockListType
		$blockListType = 'all';
		if ($type == 1)
		    $blockListType = 'committed';
		if ($type == 2)
		    $blockListType = 'uncommitted';
		    
		// Resource name
		$resourceName = self::createResourceName($containerName , $blobName);
			
		// Perform request
		$response = $this->performRequest($resourceName, '?comp=blocklist&blocklisttype=' . $blockListType, Microsoft_Http_Transport::VERB_GET, array(), false, null, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_READ);
		if ($response->isSuccessful())
		{
		    // Parse response
		    $blockList = $this->parseResponse($response);
		    
		    // Create return value
		    $returnValue = array();
		    if ($blockList->CommittedBlocks)
		    {
			    foreach ($blockList->CommittedBlocks->Block as $block)
			    {
			        $returnValue['CommittedBlocks'][] = (object)array(
			            'Name' => (string)$block->Name,
			            'Size' => (string)$block->Size
			        );
			    }
		    }
		    if ($blockList->UncommittedBlocks)
		    {
			    foreach ($blockList->UncommittedBlocks->Block as $block)
			    {
			        $returnValue['UncommittedBlocks'][] = (object)array(
			            'Name' => (string)$block->Name,
			            'Size' => (string)$block->Size
			        );
			    }
		    }
		    
		    return $returnValue;
		}
		else
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
			
	/**
	 * Copy blob
	 *
	 * @param string $sourceContainerName       Source container name
	 * @param string $sourceBlobName            Source blob name
	 * @param string $destinationContainerName  Destination container name
	 * @param string $destinationBlobName       Destination blob name
	 * @param array  $metadata                  Key/value pairs of meta data
	 * @param array  $additionalHeaders         Additional headers. See http://msdn.microsoft.com/en-us/library/dd894037.aspx for more information.
	 * @return object Partial blob properties
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function copyBlob($sourceContainerName = '', $sourceBlobName = '', $destinationContainerName = '', $destinationBlobName = '', $metadata = array(), $additionalHeaders = array())
	{
		if ($sourceContainerName === '')
			throw new Microsoft_WindowsAzure_Exception('Source container name is not specified.');
		if (!self::isValidContainerName($sourceContainerName))
		    throw new Microsoft_WindowsAzure_Exception('Source container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($sourceBlobName === '')
			throw new Microsoft_WindowsAzure_Exception('Source blob name is not specified.');
		if ($destinationContainerName === '')
			throw new Microsoft_WindowsAzure_Exception('Destination container name is not specified.');
		if (!self::isValidContainerName($destinationContainerName))
		    throw new Microsoft_WindowsAzure_Exception('Destination container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($destinationBlobName === '')
			throw new Microsoft_WindowsAzure_Exception('Destination blob name is not specified.');
		if ($sourceContainerName === '$root' && strpos($sourceBlobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
		if ($destinationContainerName === '$root' && strpos($destinationBlobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');

		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Additional headers?
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}
		
		// Resource names
		$sourceResourceName = self::createResourceName($sourceContainerName, $sourceBlobName);
		$destinationResourceName = self::createResourceName($destinationContainerName, $destinationBlobName);
		
		// Set source blob
		$headers["x-ms-copy-source"] = '/' . $this->_accountName . '/' . $sourceResourceName;

		// Perform request
		$response = $this->performRequest($destinationResourceName, '', Microsoft_Http_Transport::VERB_PUT, $headers, false, null, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);
		if ($response->isSuccessful())
		{
			return new Microsoft_WindowsAzure_Storage_BlobInstance(
				$destinationContainerName,
				$destinationBlobName,
				$response->getHeader('Etag'),
				$response->getHeader('Last-modified'),
				$this->getBaseUrl() . '/' . $destinationContainerName . '/' . $destinationBlobName,
				0,
				'',
				'',
				'',
				false,
		        $metadata
			);
		}
		else
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Get blob
	 *
	 * @param string $containerName      Container name
	 * @param string $blobName           Blob name
	 * @param string $localFileName      Local file name to store downloaded blob
	 * @param array  $additionalHeaders  Additional headers. See http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function getBlob($containerName = '', $blobName = '', $localFileName = '', $additionalHeaders = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if ($localFileName === '')
			throw new Microsoft_WindowsAzure_Exception('Local file name is not specified.');
			
		// Additional headers?
		$headers = array();
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}
		
		// Resource name
		$resourceName = self::createResourceName($containerName , $blobName);
		
		// Perform request
		$response = $this->performRequest($resourceName, '', Microsoft_Http_Transport::VERB_GET, $headers, false, null, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_READ);
		if ($response->isSuccessful())
			file_put_contents($localFileName, $response->getBody());
		else
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Get container
	 * 
	 * @param string $containerName      Container name
	 * @param string $blobName           Blob name
	 * @param array  $additionalHeaders  Additional headers. See http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 * @return Microsoft_WindowsAzure_Storage_BlobInstance
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function getBlobInstance($containerName = '', $blobName = '', $additionalHeaders = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
	        
		// Additional headers?
		$headers = array();
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}
		
        // Resource name
		$resourceName = self::createResourceName($containerName , $blobName);
		    
		// Perform request
		$response = $this->performRequest($resourceName, '', Microsoft_Http_Transport::VERB_HEAD, $headers, false, null, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_READ);
		if ($response->isSuccessful())
		{
		    // Parse metadata
		    $metadata = array();
		    foreach ($response->getHeaders() as $key => $value)
		    {
		        if (substr(strtolower($key), 0, 10) == "x-ms-meta-")
		        {
		            $metadata[str_replace("x-ms-meta-", '', strtolower($key))] = $value;
		        }
		    }

		    // Return blob
			return new Microsoft_WindowsAzure_Storage_BlobInstance(
				$containerName,
				$blobName,
				$response->getHeader('Etag'),
				$response->getHeader('Last-modified'),
				$this->getBaseUrl() . '/' . $containerName . '/' . $blobName,
				$response->getHeader('Content-Length'),
				$response->getHeader('Content-Type'),
				$response->getHeader('Content-Encoding'),
				$response->getHeader('Content-Language'),
				false,
		        $metadata
			);
		}
		else
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Get blob metadata
	 * 
	 * @param string $containerName  Container name
	 * @param string $blobName Blob name
	 * @return array Key/value pairs of meta data
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function getBlobMetadata($containerName = '', $blobName = '')
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
		
	    return $this->getBlobInstance($containerName, $blobName)->Metadata;
	}
	
	/**
	 * Set blob metadata
	 * 
	 * Calling the Set Blob Metadata operation overwrites all existing metadata that is associated with the blob. It's not possible to modify an individual name/value pair.
	 *
	 * @param string $containerName      Container name
	 * @param string $blobName           Blob name
	 * @param array  $metadata           Key/value pairs of meta data
	 * @param array  $additionalHeaders  Additional headers. See http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function setBlobMetadata($containerName = '', $blobName = '', $metadata = array(), $additionalHeaders = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
		if (count($metadata) == 0)
		    return;
		    
		// Create metadata headers
		$headers = array();
		foreach ($metadata as $key => $value)
		{
		    $headers["x-ms-meta-" . strtolower($key)] = $value;
		}
		
		// Additional headers?
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}
		
		// Perform request
		$response = $this->performRequest($containerName . '/' . $blobName, '?comp=metadata', Microsoft_Http_Transport::VERB_PUT, $headers, false, null, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);
		if (!$response->isSuccessful())
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Delete blob
	 *
	 * @param string $containerName      Container name
	 * @param string $blobName           Blob name
	 * @param array  $additionalHeaders  Additional headers. See http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function deleteBlob($containerName = '', $blobName = '', $additionalHeaders = array())
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		if ($blobName === '')
			throw new Microsoft_WindowsAzure_Exception('Blob name is not specified.');
		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		    throw new Microsoft_WindowsAzure_Exception('Blobs stored in the root container can not have a name containing a forward slash (/).');
			
		// Additional headers?
		$headers = array();
		foreach ($additionalHeaders as $key => $value)
		{
		    $headers[$key] = $value;
		}
		
		// Resource name
		$resourceName = self::createResourceName($containerName , $blobName);
		
		// Perform request
		$response = $this->performRequest($resourceName, '', Microsoft_Http_Transport::VERB_DELETE, $headers, false, null, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_WRITE);
		if (!$response->isSuccessful())
		{
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * List blobs
	 *
	 * @param string $containerName Container name
	 * @param string $prefix     Optional. Filters the results to return only blobs whose name begins with the specified prefix.
	 * @param string $delimiter  Optional. Delimiter, i.e. '/', for specifying folder hierarchy
	 * @param int    $maxResults Optional. Specifies the maximum number of blobs to return per call to Azure storage. This does NOT affect list size returned by this function. (maximum: 5000)
	 * @param string $marker     Optional string value that identifies the portion of the list to be returned with the next list operation.
	 * @param int    $currentResultCount Current result count (internal use)
	 * @return array
	 * @throws Microsoft_WindowsAzure_Exception
	 */
	public function listBlobs($containerName = '', $prefix = '', $delimiter = '', $maxResults = null, $marker = null, $currentResultCount = 0)
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
			
	    // Build query string
	    $queryString = '?restype=container&comp=list';
        if (!is_null($prefix))
	        $queryString .= '&prefix=' . $prefix;
		if ($delimiter !== '')
			$queryString .= '&delimiter=' . $delimiter;
	    if (!is_null($maxResults))
	        $queryString .= '&maxresults=' . $maxResults;
	    if (!is_null($marker))
	        $queryString .= '&marker=' . $marker;

	    // Perform request
		$response = $this->performRequest($containerName, $queryString, Microsoft_Http_Transport::VERB_GET, array(), false, null, Microsoft_WindowsAzure_Storage::RESOURCE_BLOB, Microsoft_WindowsAzure_Credentials::PERMISSION_LIST);
		if ($response->isSuccessful())
		{
		    // Return value
		    $blobs = array();
		    
			// Blobs
			$xmlBlobs = $this->parseResponse($response)->Blobs->Blob;
			if (!is_null($xmlBlobs))
			{
				for ($i = 0; $i < count($xmlBlobs); $i++)
				{
					$blobs[] = new Microsoft_WindowsAzure_Storage_BlobInstance(
						$containerName,
						(string)$xmlBlobs[$i]->Name,
						(string)$xmlBlobs[$i]->Etag,
						(string)$xmlBlobs[$i]->LastModified,
						(string)$xmlBlobs[$i]->Url,
						(string)$xmlBlobs[$i]->Size,
						(string)$xmlBlobs[$i]->ContentType,
						(string)$xmlBlobs[$i]->ContentEncoding,
						(string)$xmlBlobs[$i]->ContentLanguage,
						false
					);
				}
			}
			
			// Blob prefixes (folders)
			$xmlBlobs = $this->parseResponse($response)->Blobs->BlobPrefix;
			
			if (!is_null($xmlBlobs))
			{
				for ($i = 0; $i < count($xmlBlobs); $i++)
				{
					$blobs[] = new Microsoft_WindowsAzure_Storage_BlobInstance(
						$containerName,
						(string)$xmlBlobs[$i]->Name,
						'',
						'',
						'',
						0,
						'',
						'',
						'',
						true
					);
				}
			}
			
			// More blobs?
			$xmlMarker = (string)$this->parseResponse($response)->NextMarker;
			$currentResultCount = $currentResultCount + count($blobs);
			if (!is_null($maxResults) && $currentResultCount < $maxResults)
			{
    			if (!is_null($xmlMarker) && $xmlMarker != '')
    			{
    			    $blobs = array_merge($blobs, $this->listBlobs($containerName, $prefix, $delimiter, $maxResults, $marker, $currentResultCount));
    			}
			}
			if (!is_null($maxResults) && count($blobs) > $maxResults)
			    $blobs = array_slice($blobs, 0, $maxResults);
			
			return $blobs;
		}
		else 
		{		        
		    throw new Microsoft_WindowsAzure_Exception($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}
	
	/**
	 * Generate shared access URL
	 * 
	 * @param string $containerName  Container name
	 * @param string $blobName       Blob name
     * @param string $resource       Signed resource - container (c) - blob (b)
     * @param string $permissions    Signed permissions - read (r), write (w), delete (d) and list (l)
     * @param string $start          The time at which the Shared Access Signature becomes valid.
     * @param string $expiry         The time at which the Shared Access Signature becomes invalid.
     * @param string $identifier     Signed identifier
	 * @return string
	 */
	public function generateSharedAccessUrl($containerName = '', $blobName = '', $resource = 'b', $permissions = 'r', $start = '', $expiry = '', $identifier = '')
	{
		if ($containerName === '')
			throw new Microsoft_WindowsAzure_Exception('Container name is not specified.');
		if (!self::isValidContainerName($containerName))
		    throw new Microsoft_WindowsAzure_Exception('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');

        // Resource name
		$resourceName = self::createResourceName($containerName , $blobName);

		// Generate URL
		return $this->getBaseUrl() . '/' . $resourceName . '?' .
		    $this->_sharedAccessSignatureCredentials->createSignedQueryString(
		        $resourceName,
		        '',
		        $resource,
		        $permissions,
		        $start,
		        $expiry,
		        $identifier);
	}
	
    /**
     * Register this object as stream wrapper client
     *
     * @param  string $name Protocol name
     * @return Microsoft_WindowsAzure_Storage_Blob
     */
    public function registerAsClient($name)
    {
        self::$_wrapperClients[$name] = $this;
        return $this;
    }

    /**
     * Unregister this object as stream wrapper client
     *
     * @param  string $name Protocol name
     * @return Microsoft_WindowsAzure_Storage_Blob
     */
    public function unregisterAsClient($name)
    {
        unset(self::$_wrapperClients[$name]);
        return $this;
    }

    /**
     * Get wrapper client for stream type
     *
     * @param  string $name Protocol name
     * @return Microsoft_WindowsAzure_Storage_Blob
     */
    public static function getWrapperClient($name)
    {
        return self::$_wrapperClients[$name];
    }

    /**
     * Register this object as stream wrapper
     *
     * @param  string $name Protocol name
     */
    public function registerStreamWrapper($name = 'azure')
    {
        /**
         * @see Microsoft_WindowsAzure_Storage_Blob_Stream
         */
        require_once 'Microsoft/WindowsAzure/Storage/Blob/Stream.php';

        stream_register_wrapper($name, 'Microsoft_WindowsAzure_Storage_Blob_Stream');
        $this->registerAsClient($name);
    }

    /**
     * Unregister this object as stream wrapper
     *
     * @param  string $name Protocol name
     * @return Microsoft_WindowsAzure_Storage_Blob
     */
    public function unregisterStreamWrapper($name = 'azure')
    {
        stream_wrapper_unregister($name);
        $this->unregisterAsClient($name);
    }
    
    /**
     * Create resource name
     * 
	 * @param string $containerName  Container name
	 * @param string $blobName Blob name
     * @return string
     */
    public static function createResourceName($containerName = '', $blobName = '')
    {
		// Resource name
		$resourceName = $containerName . '/' . $blobName;
		if ($containerName === '' || $containerName === '$root')
		    $resourceName = $blobName;
		if ($blobName === '')
		    $resourceName = $containerName;
		    
		return $resourceName;
    }
	
	/**
	 * Is valid container name?
	 *
	 * @param string $containerName Container name
	 * @return boolean
	 */
    public static function isValidContainerName($containerName = '')
    {
        if ($containerName == '$root')
            return true;
            
        if (!ereg("^[a-z0-9][a-z0-9-]*$", $containerName))
            return false;
    
        if (strpos($containerName, '--') !== false)
            return false;
    
        if (strtolower($containerName) != $containerName)
            return false;
    
        if (strlen($containerName) < 3 || strlen($containerName) > 63)
            return false;
            
        if (substr($containerName, -1) == '-')
            return false;
    
        return true;
    }
    
	/**
	 * Get error message from Microsoft_Http_Response
	 * 
	 * @param Microsoft_Http_Response $response Repsonse
	 * @param string $alternativeError Alternative error message
	 * @return string
	 */
	protected function getErrorMessage(Microsoft_Http_Response $response, $alternativeError = 'Unknown error.')
	{
		$response = $this->parseResponse($response);
		if ($response && $response->Message)
		    return (string)$response->Message;
		else
		    return $alternativeError;
	}
	
	/**
	 * Generate block id
	 * 
	 * @param int $part Block number
	 * @return string Windows Azure Blob Storage block number
	 */
	protected function generateBlockId($part = 0)
	{
		$returnValue = $part;
		while (strlen($returnValue) < 64)
		{
			$returnValue = '0' . $returnValue;
		}
		
		return $returnValue;
	}
}
