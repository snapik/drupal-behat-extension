<?php
namespace Promet\Drupal\Behat\SubContext;

use Promet\Drupal\Behat\SubContext;

class MigrationContext extends SubContext
{
  /**
   * Initializes context.
   */
  public function __construct(array $parameters = array()) {
  }

  public static function getAlias() {
    return "DrupalMigration";
  }

  // @BeforeScenario @migration
  public function enableMigration($event) {
    $module = 'p2pi_migrate';
    $this->assertModuleExists($module, TRUE);
  }

  // @AfterFeature @migration
  public static function rollbackMigration(FeatureEvent $event) {
    $migration_array = migrate_migrations();
    $migrations = array_keys($migration_array);
    foreach ($migrations as $key => $machine_name) {
      $migrate = Migration::getInstance($machine_name);
      $migrate->processRollback();
    }
  }

  /**
   * @Given /^the "([^"]*)" migration has run$/
   */
  public function theMigrationHasRun($migration, $options = array()) {
    $migration = \Migration::getInstance($migration);
    $migration->prepareUpdate($options);
    $migration->processImport($options);
    $completed = \Migration::RESULT_COMPLETED;
    var_dump($completed);
    //$this->assertEquals(1, $completed, "The $migration migration did not complete.");
  }

  /**
   * @Then /^there should be (\d+) failed items$/
   */
  public function thereShouldBeFailedItems($expectedFailures) {
    //$failures = $completed;
    //var_dump($failures);
    //assertEquals(
      //$expectedFailures,
      //$failures,
      //"$failures items failed. There should have only been " . $expectedFailures . ' items failed.'
    //);
  }

  /**
   * @Given /^there should be (\d+) skipped items$/
   */
  public function thereShouldBeSkippedItems($skips) {
    //throw new PendingException();
  }
  
  /**
   * @Given /^there should be (\d+) ignored items$/
   */
  public function thereShouldBeIgnoredItems($arg1) {
    //throw new PendingException();
  }

  /**
   * @Given /^node ID (\d+) runs on the "([^"]*)" migration$/
   */
  public function nodeIdRunsOnTheMigration($nid, $migration) {
    $options['idlist'] = $nid;
    $options['force'] = TRUE;
    
    $migration = \Migration::getInstance($migration);
    $migration->prepareUpdate($options);
    $migration->processImport($options);

  }

  /**
   * Helper function to get the NID by title.
   *
   * @param string $title
   *
   * @return string
   */
  protected function getLastNodeID($title) {
    $query = new \EntityFieldQuery();
    $entities = $query->entityCondition('entity_type', 'node')
      ->propertyCondition('title', $title)
      ->propertyCondition('status', 1)
      ->execute();
    if (!empty($entities['node'])) {
      $node = end($entities['node']);
    }
    return $node->nid;
}

  /**
   * @Then /^I should see "([^"]*)" for the title$/
   */
  public function iShouldSeeForTheTitle($title) {
    
    $nid = $this->getLastNodeID($title);
    var_dump($nid);
    $node = node_load($nid);
    //var_dump($node);
    assertNotEmpty($node, "No item with the title $title was imported.");
    assertEquals(
      $title,
      $node->title,
      "Title doesn't have the value $title. Instead it has " . $node->title . '.'
    );
    return $node;
  }

  /**
   * @Given /^I should see "([^"]*)" for the "([^"]*)" field$/
   */
  public function iShouldSeeForTheField($fieldData, $field) {
    var_dump($node);
    assertEquals(
      $fieldData,
      $node->$field,
      "$field doesn't have the value $fieldData. Instead it has " . $node->$field . '.'
    );
  }

}
