<?php

require_once 'Microsoft/WindowsAzure/Storage/Blob.php';

/**
 * Controller for the Azure Blob Storage
 */
class BlobController extends BaseController implements ControllerInterface
{
  /**
   * Initial page of the Blob controller, lists the available Blob containers
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeIndex(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $blob_object = $this->getBlobObject($config);
    $twig = $this->getTwig($config, 'blob');
    $template = $twig->loadTemplate('container-list.tmpl');

    $blob_containers = $blob_object->listContainers();
    $containers = array();
    foreach($blob_containers as $container)
    {
      $containers[] = array('name' => $container->Name);
    }

    return $template->render(array('containercount' => count($containers), 'containers' => $containers));
  }

  /**
   * Show creation form for container and handle submission of the form
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeCreatecontainer(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $blob_object = $this->getBlobObject($config);
    $twig = $this->getTwig($config, 'blob');

    if ($request->getMethod() == sfWebRequest::POST)
    {

      if ($blob_object->containerExists($request->getParameter('containername')))
      {
        $template = $twig->loadTemplate('error.tmpl');
        return $template->render(array('error' => 'A container named '.$request->getParameter('containername').' already exists'));
      }
      $template = $twig->loadTemplate('create-container-success.tmpl');

      $blob_object->createContainer($request->getParameter('containername'));
      
      return $template->render(array('containername' => $request->getParameter('containername')));
    }
    else
    {
      $template = $twig->loadTemplate('create-container.tmpl');

      return $template->render(array());
    }
  }

  /**
   * Delete the selected container
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeDeletecontainer(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $blob_object = $this->getBlobObject($config);

    $twig = $this->getTwig($config, 'blob');
    $template = $twig->loadTemplate('delete-container-success.tmpl');

    $blob_object->deleteContainer($request->getParameter('container'));

    return $template->render(array('container' => $request->getParameter('container')));
  }

  /**
   * Show a list of all blobs in the chosen container
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeBloblist(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $blob_object = $this->getBlobObject($config);

    $twig = $this->getTwig($config, 'blob');
    $template = $twig->loadTemplate('bloblist.tmpl');

    $blobs = array();
    $bloblist = $blob_object->listBlobs($request->getParameter('container'));

    foreach($bloblist as $blob)
    {
      $blobs[] = array('name' => $blob->Name, 'contenttype' => $blob->ContentType);
    }

    return $template->render(array('blobcount' => count($blobs), 'blobs' => $blobs, 'container' => $request->getParameter('container')));
  }

  /**
   * Show and handle blob creation form
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return void
   */
  public function executeCreateblob(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $blob_object = $this->getBlobObject($config);

    $twig = $this->getTwig($config, 'blob');

    if ($request->getMethod() == sfWebRequest::POST)
    {
      $blob_object->putBlob($request->getParameter('container'), $_FILES['blobfile']['name'], $_FILES['blobfile']['tmp_name']);

      $template = $twig->loadTemplate('createblob-success.tmpl');

      $content = $template->render(array('container' => $request->getParameter('container'), 'blobname' => $request->getParameter('blobname')));
    }
    else
    {
      $template = $twig->loadTemplate('createblob.tmpl');

      $content = $template->render(array('container' => $request->getParameter('container')));
    }

    return $content;
  }

  /**
   * Handle the download of a blob
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return
   */
  public function executeBlobdownload(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $blob_object = $this->getBlobObject($config);

    $tmpfile = tempnam(sys_get_temp_dir(), 'MTD_');
    $blob_object->getBlob($request->getParameter('container'), $request->getParameter('blob'), $tmpfile);

    header('Content-Disposition: attachment; filename='.$request->getParameter('blob'));
    header('Content-Type: application/octet-stream');

    readfile($tmpfile);
    unlink($tmpfile);
    
    return null;
  }

  /**
   * Get the blog object to be used
   *
   * @param array $config
   * @return Microsoft_WindowsAzure_Storage_Blob
   */
  protected function getBlobObject($config)
  {
    return new Microsoft_WindowsAzure_Storage_Blob($config['sqlazure_blob_server'], $config['sqlazure_user'], $config['sqlazure_pass']);
  }
}