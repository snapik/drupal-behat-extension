<?php
namespace Promet\Drupal\Behat\SubContext;

use Promet\Drupal\Behat\SubContext;

class ContentContext extends SubContext
{
    public $content;

    public static function getAlias() {
        return "DrupalContent";
    }

    /**
     * @Given /^(\d+) "([^"]*)" ([\w ]+) exist[s]?$/
     */
    public function createContent($amount, $bundle, $entityTypeLabel) {
        $entityTypeLabel = preg_replace("/s$/", "", $entityTypeLabel);
        $selectedEntityType = NULL;
        foreach (entity_get_info() as $entityType => $entityInfo) {
            if (strtolower($entityInfo['label']) == strtolower($entityTypeLabel)) {
                $selectedEntityType = $entityType;
                break;
            }
        }
        if (empty($selectedEntityType)) {
            throw new \Exception("Entity Type $entityTypeLabel doesn't exist.");
        }
        for ($i=0; $i<$amount; $i++) {
            $this->content[$selectedEntityType][$bundle][$i] = entity_create($selectedEntityType, array('bundle' => $bundle));
        }
    }
}
