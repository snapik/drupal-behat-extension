<?php
namespace Promet\Drupal\Behat\SubContext;

use Promet\Drupal\Behat\SubContext;

class UserContext extends SubContext
{
   public static function getAlias() {
    return "DrupalUser";
  }
  /**
   * @Then /^there should be a user with email "([^"]*)"$/
   */
  public function thereShouldBeAUserWithEmail($email)
  {
    $efq = new \EntityFieldQuery();
    $users = $efq->entityCondition('entity_type', 'user')
    ->propertyCondition('mail', $email)
    ->count()
    ->execute();
    $this->assertNotEquals(0, $users, 'Failed to find user with email address: ' . $email);
  }
}