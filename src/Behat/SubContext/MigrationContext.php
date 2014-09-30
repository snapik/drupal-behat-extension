<?php
namespace Promet\Drupal\Behat\SubContext;

use Promet\Drupal\Behat\SubContext;

class MigrationContext extends SubContext
{
  // Variables for use in class
  public $entity;
  public $migration;

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
   * Helper function to get the destination object by source ID.
   *
   * @param string $migration
   *   The machine name of the migration
   *
   * @param string $sourceID
   *
   * @return object
   */
  public function getLastEntity($migration, $sourceID) {
    $mapping_table = 'migrate_map_' . strtolower($migration);
    $eid = db_select($mapping_table, 'mt')
      ->fields('mt', array('destid1'))
      ->condition('sourceid1', $sourceID,'=')
      ->execute()
      ->fetchAssoc();
    $eid = array_pop($eid);
    $entity_object = entity_load('node', array($eid));
    $entity_object = array_pop($entity_object);
    $wrapper = entity_metadata_wrapper('node', $entity_object);
    return $wrapper;
  }

  /**
   * @Given /^the "([^"]*)" migration has run for entities with IDs "([^"]*)"$/
   */
  public function theMigrationHasRunForEntitiesWithIds($migration, $eids) {
    $options['idlist'] = $eids;
    $options['force'] = TRUE;
    $this->migration = \Migration::getInstance($migration);
    $this->migration->prepareUpdate($options);
    $this->migration->processImport($options);
    $completed = \Migration::RESULT_COMPLETED;
    assertEquals(1, $completed, "The $migration migration did not complete.");
  }

  /**
   * @Then /^there should be (\d+) failed items$/
   */
  public function thereShouldBeFailedItems($expectedFailures) {
    $failures = $this->migration->processed_since_feedback - $this->migration->successes_since_feedback;
    assertEquals(
      $expectedFailures,
      $failures,
      "$failures items failed. There should have only been " . $expectedFailures . ' items failed.'
    );
  }

  /**
   * @Given /^there should be (\d+) imported items$/
   */
  public function thereShouldBeImportedItems($expectedSuccesses) {
    $successes = $this->migration->successes_since_feedback;
    assertEquals(
      $expectedSuccesses,
      $successes,
      "$successes items were imported. There should have been " . $successes . ' items imported.'
    );
  }

  /**
   * @Given /^the "([^"]*)" migration has run for the entity with ID "([^"]*)"$/
   */
  public function theMigrationHasRunForTheEntityWithId($migration, $eid) {
    $options['idlist'] = $eid;
    $options['force'] = TRUE;
    $this->migration = \Migration::getInstance($migration);
    $this->migration->prepareUpdate($options);
    $this->migration->processImport($options);
    $completed = \Migration::RESULT_COMPLETED;
    assertEquals(1, $completed, "The $migration migration did not complete.");
    $this->entity = self::getLastEntity($migration, $eid);
  }

  /**
   * @Then /^I should see "([^"]*)" for the title$/
   */
  public function iShouldSeeForTheTitle($title) {
    $entity_title = $this->entity->title->value(array('sanitize' => TRUE));
    assertNotEmpty($entity_title, "No item with the title $title was imported.");
    assertEquals(
      $title,
      $entity_title,
      "Title doesn't have the value $title. Instead it has " . $entity_title . '.'
    );
  }

  /**
   * @Given /^I should see "([^"]*)" for the "([^"]*)" field$/
   */
  public function iShouldSeeForTheField($fieldData, $field) {
    $entity_field = $this->entity->$field;
    var_dump($entity_field);
    assertEquals(
      $fieldData,
      $entity_field,
      "$field doesn't have the value $fieldData."
    );
  }

}
