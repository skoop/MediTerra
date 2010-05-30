<?php
/**
 * This file is the front controller
 */

set_include_path(dirname(__FILE__).'/../lib'.PATH_SEPARATOR.dirname(__FILE__).'/../lib/vendor'.PATH_SEPARATOR.get_include_path());

// get the configuration
require_once(dirname(__FILE__).'/../config/config.php');

// include classes that are always used
require_once('Symfony/sfException.class.php');
require_once('Symfony/event_dispatcher/sfEvent.php');
require_once('Symfony/event_dispatcher/sfEventDispatcher.php');
require_once('Symfony/sfParameterHolder.class.php');
require_once('Symfony/sfToolkit.class.php');
require_once('Symfony/request/sfRequest.class.php');
require_once('Symfony/request/sfWebRequest.class.php');
require_once('Symfony/response/sfResponse.class.php');
require_once('Symfony/response/sfWebResponse.class.php');
require_once('Twig/Autoloader.php');
require_once('interfaces/ControllerInterface.php');
require_once('controllers/BaseController.php');
require_once('output/MediTerraOutput.php');

// initialize Twig autoloader
Twig_Autoloader::register();

// initialize dispatcher
$dispatcher = new sfEventDispatcher();
// initialize request
$request = new sfWebRequest($dispatcher);

$controller_name = $request->getParameter('controller');
if (empty($controller_name) || !in_array($controller_name, array('BlobController', 'TableController', 'QueueController')))
{
  $controller_name = 'IndexController';
}
require_once(dirname(__FILE__).'/../controllers/'.$controller_name.'.php');

$controller = new $controller_name();
$content = $controller->execute($dispatcher, $request, $config);

if (!is_null($content))
{
  // get the template path
  $template_file = MediTerraOutput::getTemplatePath($config['template']);

  // create response
  $response = new sfWebResponse($dispatcher);
  $response->setContent(MediTerraOutput::decorate($content, $template_file));
  // send response
  $response->send();
}