<?php

/**
 * Initial controller for homepage and main actions
 */
class IndexController extends BaseController implements ControllerInterface
{
  /**
   * Method handling the homepage execution
   * 
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @return void
   */
  protected function executeIndex(sfEventDispatcher $dispatcher, sfWebRequest $request)
  {
    return 'Welcome!';
  }
}