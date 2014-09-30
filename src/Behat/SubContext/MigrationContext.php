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

  /**
   * TO-DO: Make this work.
   * @AfterFeature @migration
   */
  public static function rollbackMigration(FeatureEvent $event) {
    $migration_array = migrate_migrations();
    $migrations = array_keys($migration_array);
    foreach ($migrations as $key => $machine_name) {
      $migrate = Migration::getInstance($machine_name);
      $migrate->processRollback();
    }
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

}
