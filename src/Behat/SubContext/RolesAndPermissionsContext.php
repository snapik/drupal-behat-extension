<?php
namespace Promet\Drupal\Behat\SubContext;

use Promet\Drupal\Behat\SubContext;

class RolesAndPermissionsContext extends SubContext
{
   public static function getAlias() {
    return "DrupalRolesAndPermissions";
  }
  /**
   * @Then /^the "(?P<role>[^"]*)" role should have the permission "(?P<permission>[^"]*)"$/
   */
  public function theRoleShouldHaveThePermission($role, $permission) {
    $rolesWithPermission = user_roles(FALSE, $permission);
    assertContains(strtolower($role), $rolesWithPermission);
  }

  /**
   * @Then /^the "(?P<role>[^"]*)" role should not have the permission "(?P<permission>[^"]*)"$/
   */
  public function theRoleShouldNotHaveThePermission($role, $permission) {
    $rolesWithPermission = user_roles(FALSE, $permission);
    assertNotContains(strtolower($role), $rolesWithPermission);
  }

  /**
   * @Then /^all roles should have the permission "(?P<permission>[^"]*)"$/
   */
  public function allRolesShouldHaveThePermission($permission) {
    $rolesWithPermission = user_roles(FALSE, $permission);
    assertNotEmpty($rolesWithPermission);
  }

  /**
   * @Then /^all roles should not have the permission "(?P<permission>[^"]*)"$/
   */
  public function allRolesShouldNotHaveThePermission($permission) {
    $rolesWithPermission = user_roles(FALSE, $permission);
    assertEmpty($rolesWithPermission);
  }

}