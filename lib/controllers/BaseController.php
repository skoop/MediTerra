<?php

/**
 * Abstract class for controllers with global methods
 */
abstract class BaseController
{
  /**
   * Main controller execution handler
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @param array $config
   * @return string
   */
  public function execute(sfEventDispatcher $dispatcher, sfWebRequest $request, $config)
  {
    $method = $this->getMethod($request->getParameter('action'));
    return $this->$method($dispatcher, $request, $config);
  }

  /**
   * Get the method name of the method to call
   * 
   * @param string $action
   * @return string
   */
  protected function getMethod($action)
  {
    if (empty($action))
    {
      $action = 'Index';
    }
    $method = 'execute'.ucfirst($action);
    if (is_callable(array($this, $method)))
    {
      return $method;
    }
    return 'executeIndex';
  }
}