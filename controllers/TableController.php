<?php

require_once('Microsoft/WindowsAzure/Storage/Table.php');
require_once('table/MediTerraEntity.php');

/**
 * Controller for the Azure Table Storage
 */
class TableController extends BaseController implements ControllerInterface
{
  /**
   * Listing of tables, and options for it
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeIndex(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $twig = $this->getTwig($config);
    $template = $twig->loadTemplate('list.tmpl');
    $table_object = $this->getTableObject($config);
    $result = $table_object->listTables();
    $tablecount = count($result);
    $tables = array();

    foreach ($result as $table)
    {
      $tables[] = array('name' => $table->Name, 'id' => $table->Id, 'href' => $table->Href, 'urlid' => base64_encode($table->Id));
    }
    return $template->render(array('tablecount' => $tablecount, 'tables' => $tables));
  }

  /**
   * Display of creation form and processing of said form
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeCreate(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $twig = $this->getTwig($config);
    $table_object = $this->getTableObject($config);
    $content = '';
    if ($request->getMethod() == sfWebRequest::POST)
    {
      // handle posting
      if(!$table_object->tableExists($request->getParameter('tablename')))
      {
        $table_object->createTable($request->getParameter('tablename'));
        $template = $twig->loadTemplate('create-success.tmpl');
        $content = $template->render(array('tablename' => $request->getParameter('tablename')));
      }
      else
      {
        // show error: table already exists
        $template = $twig->loadTemplate('error.tmpl');
        $content = $template->render(array('error' => 'A table named '.$request->getParameter('tablename').' already exists'));
      }
    }
    else
    {
      // display form
      $template = $twig->loadTemplate('create.tmpl');
      $content = $template->render(array());
    }

    return $content;
  }

  /**
   * Process delete requests
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeDelete(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $twig = $this->getTwig($config);
    if ($request->getMethod() == sfWebRequest::POST)
    {
      $table_object = $this->getTableObject($config);
      $table_object->deleteTable($request->getParameter('table'));

      $template = $twig->loadTemplate('delete-success.tmpl');
      $content = $template->render(array('tablename' => $request->getParameter('table')));
    }
    else
    {
      $template = $twig->loadTemplate('error.tmpl');
      $content = $template->render(array('error' => 'Delete requested wasnt POSTed'));
    }

    return $content;
  }

  /**
   * List the entities in the table
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeEntitylist(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $twig = $this->getTwig($config);
    $template = $twig->loadTemplate('entity-list.tmpl');
    
    $table_object = $this->getTableObject($config);
    $result = $table_object->retrieveEntities($request->getParameter('table'));

    $entities = array();
    foreach($result as $entity)
    {
      $entities[] = array('timestamp' => date('d-m-Y G:i', strtotime($entity->getTimestamp())), 'partitionid' => $entity->getPartitionKey(), 'rowid' => $entity->getRowKey());
    }

    $content = $template->render(array('entities' => $entities, 'entitycount' => count($entities), 'table' => $request->getParameter('table')));

    return $content;
  }

  /**
   * Process the deletion of an entity from a table
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function executeEntitydelete(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $twig = $this->getTwig($config);
    $template = $twig->loadTemplate('entitydelete-success.tmpl');

    $table_object = $this->getTableObject($config);

    $entity = $table_object->retrieveEntityById($request->getParameter('table'), $request->getParameter('partition'), $request->getParameter('row'), 'Microsoft_WindowsAzure_Storage_DynamicTableEntity');
    $table_object->deleteEntity($request->getParameter('table'), $entity);

    return $template->render(array('table' => $request->getParameter('table')));
  }

  /**
   * Instantiate a new table object with the passed configuration
   *
   * @param array $config
   * @return Microsoft_WindowsAzure_Storage_Table
   */
  protected function getTableObject($config)
  {
    return new Microsoft_WindowsAzure_Storage_Table($config['sqlazure_server'], $config['sqlazure_user'], $config['sqlazure_pass']);
  }

  /**
   * Get a new instance of Twig
   * 
   * @param array $config
   * @return Twig_Environment
   */
  protected function getTwig($config)
  {
    $loader = new Twig_Loader_Filesystem(dirname(__FILE__).'/../templates/'.$config['template'].'/table');
    return new Twig_Environment($loader, array('cache' => false));
  }
}