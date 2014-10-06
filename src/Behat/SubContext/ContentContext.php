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
     * @Given /^(an|a|\d+) "([^"]*)" ([\w ]+) exist[s]?$/
     */
    public function createContent($amount, $bundleLabel, $entityTypeLabel) {
        $entityTypeLabel = preg_replace("/s$/", "", $entityTypeLabel);
        $selectedEntityType = NULL;
        if (in_array($amount, array('an', 'a'))) {
            $amount = 1;
        }
        foreach (entity_get_info() as $entityType => $entityInfo) {
            if (strtolower($entityInfo['label']) == strtolower($entityTypeLabel)) {
                $selectedEntityType = $entityType;
                $selectedEntityInfo = $entityInfo;
                break;
            }
        }
        if (empty($selectedEntityType)) {
            throw new \Exception("Entity Type $entityTypeLabel doesn't exist.");
        }
        foreach ($selectedEntityInfo['bundles'] as $bundleMachineName => $bundle){
            if (strtolower($bundle['label']) == strtolower($bundleLabel)) {
                $bundle = $bundleMachineName;
                break;
            }

        }
        for ($i=0; $i<$amount; $i++) {
            switch ($selectedEntityType) {
                case 'taxonomy_term':
                    foreach(taxonomy_vocabulary_get_names() as $taxonomyMachineName => $taxonomyInfo) {
                        if(strtolower($taxonomyInfo->machine_name) == $bundle) {
                            $vocabularyId = $taxonomyInfo->vid;
                        }
                    }
                    // @TODO Should rewrite to entity_metadata_wrapper().
                    $term = entity_property_values_create_entity($selectedEntityType, array(
                        'vocabulary' => $vocabularyId,
                    ))->save()->value();
                    $this->content[$selectedEntityType][$bundle][$i] = $term;
                    break;
                default:
                    $entity_object = entity_create(
                        $selectedEntityType,
                        array( $selectedEntityInfo['entity keys']['bundle'] => strtolower($bundle))
                    );
                    $wrapper = entity_metadata_wrapper($selectedEntityType, $entity_object);
                    $wrapper->save();
                    $this->content[$selectedEntityType][$bundle][$i] = $wrapper;
                    break;
            }
        }
    }
}
