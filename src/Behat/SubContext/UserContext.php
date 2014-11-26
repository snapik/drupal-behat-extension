<?php
namespace Promet\Drupal\Behat\SubContext;

use Promet\Drupal\Behat\SubContext;

class UserContext extends SubContext
{
   public static function getAlias() {
    return "DrupalUser";
  }
  /**
   * @Then /^there (should|should not) be a user with the email "([^"]*)"$/
   */
  public function thereShouldBeAUserWithEmail($not, $email) {
    $not = $not == 'should not';
    $users = db_select('users', 'u')
      ->fields('u', array('uid'))
      ->condition('u.mail', $email)
      ->execute()
      ->fetchCol();
    $users = count($users);
    if ($not) {
      assertEquals(0, $users, 'Found user with email address: ' . $email);
    }
    else {
      assertNotEquals(0, $users, 'Failed to find user with email address: ' . $email);
    }
  }

  /**
   * @Then /^there (should|should not) be a user with the username "([^"]*)"$/
   */
  public function thereShouldBeAUserWithUsername($not, $username) {
    $not = $not == 'should not';
    $users = db_select('users', 'u')
      ->fields('u', array('uid'))
      ->condition('u.name', $username)
      ->execute()
      ->fetchCol();
    $users = count($users);
    if ($not) {
      assertEquals(0, $users, 'Found user with email address: ' . $email);
    }
    else {
      assertNotEquals(0, $users, 'Failed to find user with email address: ' . $email);
    }
  }
}
