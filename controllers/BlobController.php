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
   * 
   * @param  $config
   * @return Microsoft_WindowsAzure_Storage_Blob
   */
  protected function getBlobObject($config)
  {
    return new Microsoft_WindowsAzure_Storage_Blob($config['sqlazure_blob_server'], $config['sqlazure_user'], $config['sqlazure_pass']);
  }
}