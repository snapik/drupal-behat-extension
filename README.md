Drupal Behat Extension
======================

The commonly used Behat funcitonality used by Promet in Behat testing using Drupal.

Installation
------------

Install using composer. Simply add a similar line to your composer.json.

```
    "require-dev": {
      promet/drupal-behat-extension": "*",
    }
```

Usage
-----

For basic usage, simply extend your `FeatureContext` class with `PrometDrupalContext` much like so

```
    <?php
    use Promet\Drupal\Behat\PrometDrupalContext;

    class FeatureContext extends PrometDrupalContext
    {
      public function __construct($parameters) {
        parent::__construct($parameters);

        $this->useContext('YourSubContext', new YourSubContext());
      }
    }
```

This will give you full access to all of the already defined helper functions and subcontexts built into this library.

Configuration
-------------

This is the list of environment variables that can be set to help you configure multiple environments.

* **DRUPAL_BASE_URL** - the URL for tests to begin with. If not set, this will be what ever is in your behat.yml.
* **DRUPAL_BASIC_AUTH_USERNAME** - the HTTP Basic Auth username to enter before visiting a page.
* **DRUPAL_BASIC_AUTH_PASS** - the HTTP Basic Auth password to enter before visiting a page.