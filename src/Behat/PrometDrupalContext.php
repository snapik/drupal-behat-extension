<?php
namespace Promet\Drupal\Behat;

use Promet\Drupal\Behat\SubContext\ContentTypeContext;
use Promet\Drupal\Behat\SubContext\ContentContext;
use Promet\Drupal\Behat\SubContext\UserContext;
use Promet\Drupal\Behat\SubContext\MigrationContext;
use Promet\Drupal\Behat\SubContext\RolesAndPermissionsContext;
use Drupal\DrupalExtension\Context\DrupalContext;

// PHPUnit adds itself to the include path via composer.
require_once 'src/Framework/Assert/Functions.php';

class PrometDrupalContext extends DrupalContext
{
  private $assertDelegateClass = '\PHPUnit_Framework_Assert';
  private $drupalSession = FALSE;
  public function __construct(array $parameters) {
    $parameters['parent_context'] = $this;
    $this->useContext('DrupalUser', new UserContext($parameters));
    $this->useContext('DrupalContentType', new ContentTypeContext($parameters));
    $this->useContext('DrupalMigration', new MigrationContext($parameters));
    $this->useContext('DrupalRolesAndPermissions', new RolesAndPermissionsContext($parameters));
    $this->useContext('DrupalContent', new ContentContext($parameters));
  }
  public function beforeScenario($event)
  {
    parent::beforeScenario($event);
    // @todo provide our own mail system to ameliorate ensuing ugliness.
    if ($event instanceof ScenarioEvent) {
      if ($event->getScenario()->hasTag('mail')) {

        if (module_exists('devel')) {
          variable_set('mail_system', array('default-system' => 'DevelMailLog'));
        }
        else {
          throw new \Exception('You must ensure that the devel module is enabled');
        }
        $fs = new \Filesystem();
        if ($mail_path = $event->getScenario()->getTitle()) {
          $fs->remove('/tmp/' . $mail_path);
          $fs->mkdir($mail_path);
        }
        variable_set('devel_debug_mail_directory', $mail_path);
              // NB: DevelMailLog is going to replace our separator with __.
        variable_set('devel_debug_mail_file_format', '%to::%subject');
        $this->mail = new \DevelMailLog();
      }
    }
    if (!$this->drupalSession) {
      $_SERVER['SERVER_SOFTWARE'] = 'foo';
      $this->drupalSession = (object) array(
        'name' => session_name(),
        'id'   => session_id()
      );
      $_SESSION['foo'] = 'bar';
      drupal_session_commit();
    }
    session_name($this->drupalSession->name);
    session_id($this->drupalSession->id);
    $_COOKIE[session_name()] = session_id();
    drupal_session_start();
    $base_url = getenv('DRUPAL_BASE_URL');
    if (!empty($base_url)) {
      $this->setMinkParameter('base_url',$base_url);
    }
    $userName = getenv('DRUPAL_BASIC_AUTH_USERNAME');
    $pass = getenv('DRUPAL_BASIC_AUTH_PASS');
    foreach (array('selenium2', 'goutte') as $session) {
      $session = $this->getMink()->getSession($session);
      $session->visit($this->locatePath('/index.php?foo=bar'));
      if (!empty($userName) && !empty($pass)) {
          $this->getMink()->getSession()->setBasicAuth($userName,$pass);
      }
    }
  }
  public function afterScenario($event)
  {
    // Allow clean up.
    parent::afterScenario($event);
    $this->drupalSession = FALSE;
  }
  public function getDrupalSession() {
    return $this->drupalSession;
  }
  public function __call($name, array $args = array())
  {
    // Allow all calls to assert function against $this to go to PHPUnit.
    if (strpos($name, 'assert') !== FALSE) {
      return call_user_func_array("{$this->assertDelegateClass}::$name", $args);
    }
  }

  /**
   * Helper function to login the current user.
   */
  public function login() {
    // Check if logged in.
    if ($this->loggedIn()) {
      $this->logout();
    }

    if (!$this->user) {
      throw new \Exception('Tried to login without a user.');
    }

    $this->getSession()->visit($this->locatePath('/user'));
    $element = $this->getSession()->getPage();
    $element->fillField($this->getDrupalText('username_field'), $this->user->name);
    $element->fillField($this->getDrupalText('password_field'), $this->user->pass);
    $submit = $element->findButton($this->getDrupalText('log_in'));
    if (empty($submit)) {
      throw new \Exception(sprintf("No submit button at %s", $this->getSession()->getCurrentUrl()));
    }

    // Log in.
    $submit->click();

    // Check if Legal module exist.
    if (module_exists('legal')){
      // Get second step page.
      $element = $this->getSession()->getPage();

      $confirm = $element->findButton('Confirm');
      if (empty($confirm)) {
        throw new \Exception(sprintf("No submit button at %s", $this->getSession()->getCurrentUrl()));
      }

      $element->checkField('legal_accept');

      // Confirm.
      $confirm->click();
    }

    if (!$this->loggedIn()) {
      throw new \Exception(sprintf("Failed to log in as user '%s' with role '%s'", $this->user->name, $this->user->role));
    }
  }
}
