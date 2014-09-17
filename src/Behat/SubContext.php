<?php
namespace Promet\Drupal\Behat;

use Behat\Behat\Context\BehatContext;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

class SubContext extends BehatContext implements DrupalSubContextInterface
{
  private $parentContext;

  public static function getAlias() {
    return "Drupal" . str_replace('Context','',get_called_class());
  }

  public function __construct($parameters) {
    $this->parentContext = $parameters['parent_context'];
  }

  public function __call($name, array $args = array())
  {
    // Allow all calls to assert function against $this to go to PHPUnit.
    if (strpos($name, 'assert') !== FALSE) {
      return $this->$name;
    }
  }
}
