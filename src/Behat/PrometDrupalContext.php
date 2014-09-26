<?php
namespace Promet\Drupal\Behat;

use Promet\Drupal\Behat\SubContext\ContentTypeContext;
use Promet\Drupal\Behat\SubContext\UserContext;
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
    $base_url = getenv('DRUPAL_BASE_URL');
    if (!empty($base_url)) {
      $this->setMinkParameter('base_url',$base_url);
    }
  }
  public function beforeScenario($event)
  {
    parent::beforeScenario($event);
    // @todo provide our own mail system to ameliorate ensuing ugliness.
    if ($event->getScenario()->hasTag('mail')) {

      if (module_exists('devel')) {
        variable_set('mail_system', array('default-system' => 'DevelMailLog'));
      }
      else {
        throw new \Exception('You must ensure that the devel module is enabled');
      }
      if ($event instanceof ScenarioEvent) {
        $fs = new Filesystem();
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
    foreach (array('selenium2', 'goutte') as $session) {
      $session = $this->getMink()->getSession($session);
      $session->visit($this->locatePath('/index.php?foo=bar'));
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
}