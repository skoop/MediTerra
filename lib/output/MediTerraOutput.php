<?php

/**
 * Output-related functionality class
 *
 * @throws Exception
 */
class MediTerraOutput
{
  /**
   * Decorate the passed content with the passed template
   *
   * @static
   * @param  $content
   * @param  $template_file
   * @return string
   */
  static public function decorate($content, $template_file)
  {
    $template = file_get_contents($template_file);
    return str_replace('{{CONTENT}}', $content, $template);
  }

  /**
   * Get the template path for the given template name
   *
   * @static
   * @throws Exception
   * @param  $template_name
   * @return string
   */
  static public function getTemplatePath($template_name)
  {
    $template_file = dirname(__FILE__).'/../../templates/'.$template_name.'/template.php';
    if (file_exists($template_file))
    {
      return $template_file;
    }
    else
    {
      throw new Exception('Template not found');
    }
  }
}