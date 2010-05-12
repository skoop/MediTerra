<?php

/**
 * Interface for all controllers
 */
Interface ControllerInterface
{
  /**
   * Execute method is called to trigger controller execution, and returns the parsed page contents
   *
   * @abstract
   * @param sfEventDispatcher $dispatcher
   * @param sfWebRequest $request
   * @return string
   */
  public function execute(sfEventDispatcher $dispatcher, sfWebRequest $request);
}