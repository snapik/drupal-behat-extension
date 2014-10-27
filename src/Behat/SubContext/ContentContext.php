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
    public function createEntity($amount, $bundleLabel, $entityTypeLabel) {
        if (in_array($amount, array('an', 'a'))) {
            $amount = 1;
        }
        $entityTypeLabel = preg_replace("/s$/", "", $entityTypeLabel);
        $entityType = $this->getEntityTypeFromLabel($entityTypeLabel);
        if (empty($entityType)) {
            throw new \Exception("Entity Type $entityTypeLabel doesn't exist.");
        }
        $bundle = $this->getEntityBundleFromLabel($bundleLabel, $entityType);
        $entityInfo = entity_get_info($entityType);
        for ($i=0; $i<$amount; $i++) {
            $entityObject = entity_create(
                $entityType,
                array($entityInfo['entity keys']['bundle'] => $bundle)
            );
            $wrapper = entity_metadata_wrapper($entityType, $entityObject);
            $wrapper->save();
            $this->content[$entityType][$bundle][$i] = $wrapper;
        }
    }

    public function getEntityTypeFromLabel($label) {
        $selectedEntityType = NULL;
        foreach (entity_get_info() as $entityType => $entityInfo) {
            if (strtolower($entityInfo['label']) == strtolower($label)) {
                $selectedEntityType = $entityType;
                break;
            }
        }
        return $selectedEntityType;
    }

    public function getEntityBundleFromLabel($label, $type) {
        $entityInfo = entity_get_info($type);
        $selectedBundle = NULL;
        foreach ($entityInfo['bundles'] as $bundleMachineName => $bundle){
            if (strtolower($bundle['label']) == strtolower($label)) {
                $selectedBundle = $bundleMachineName;
                break;
            }
        }
        return $selectedBundle;
    }

    /**
     * @Given /^(?:that|those) "([^"]*)" ([\w ]+) (?:has|have) "([^"]*)" set to "([^"]*)"$/
     */
    public function setEntityPropertyValue($bundleLabel, $entityTypeLabel, $fieldLabel, $rawValue) {
        $entityTypeLabel = preg_replace("/s$/", "", $entityTypeLabel);
        $entityType = $this->getEntityTypeFromLabel($entityTypeLabel);
        if (empty($entityType)) {
            throw new \Exception("Entity Type $entityTypeLabel doesn't exist.");
        }
        $bundle = $this->getEntityBundleFromLabel($bundleLabel, $entityType);
        $mainContext = $this->getMainContext();
        $wrappers = $mainContext->getSubcontext('DrupalContent')->content[$entityType][$bundle];

        foreach ($wrappers as $wrapper) {
            foreach ($wrapper->getPropertyInfo() as $key => $wrapper_property) {
                if ($fieldLabel == $wrapper_property['label']) {
                    $fieldMachineName = $key;
                    if ($wrapper_property['type'] == 'boolean') {
                        $value = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN);
                    }
                    elseif ($fieldInfo = field_info_field($fieldMachineName)) {
                        // @todo add condition for each field type.
                        switch ($fieldInfo['type']) {
                            case 'entityreference':
                                $relatedEntityType = $fieldInfo['settings']['target_type'];
                                $targetBundles = $fieldInfo['settings']['handler_settings']['target_bundles'];
                                $query = new \EntityFieldQuery();
                                $query->entityCondition('entity_type', $relatedEntityType);

                                // Adding special conditions.
                                if (count($targetBundles) > 1) {
                                    $relatedBundles = array_keys($targetBundles);
                                    $query->entityCondition('bundle', $relatedBundles, 'IN');
                                }
                                else {
                                    $relatedBundles = reset($targetBundles);
                                    $query->entityCondition('bundle', $relatedBundles);
                                }
                                if ($relatedEntityType == 'node') {
                                    $query->propertyCondition('title', $rawValue);
                                }
                                if ($relatedEntityType == 'taxonomy_term' || $relatedEntityType == 'user') {
                                    $query->propertyCondition('name', $rawValue);
                                }
                                $results = $query->execute();
                                if (empty($results)) {
                                    throw new \Exception("Entity that you want relate to current $entityTypeLabel doesn't exist.");
                                }
                                $value = array_keys($results[$relatedEntityType]);
                                break;

                            case 'image':
                                try {
                                    var_dump($rawValue);
                                    $image = file_get_contents($rawValue);
                                    $value = new \stdClass();
                                    $value = file_save_data($image, 'public://', FILE_EXISTS_REPLACE);
                                    var_dump($value);
                                    // $url = 'http://okay.jpg.to/';
                                    // $data = file_get_contents($url);
                                    // $destination = 'public://';
                                    // $replace = FILE_EXISTS_REPLACE;
                                    // // $value = file_save_data($image, 'public://', FILE_EXISTS_REPLACE);



                                    //   global $user;

                                    //   if (empty($destination)) {
                                    //     $destination = file_default_scheme() . '://';
                                    //   }
                                    //   if (!file_valid_uri($destination)) {
                                    //     watchdog('file', 'The data could not be saved because the destination %destination is invalid. This may be caused by improper use of file_save_data() or a missing stream wrapper.', array('%destination' => $destination));
                                    //     drupal_set_message(t('The data could not be saved, because the destination is invalid. More information is available in the system log.'), 'error');
                                    //     return FALSE;
                                    //   }

                                    //   if ($uri = file_unmanaged_save_data($data, $destination, $replace)) {
                                    //     var_dump($uri);
                                    //     // Create a file object.
                                    //     $file = new \stdClass();
                                    //     $file->fid = NULL;
                                    //     $file->uri = $uri;
                                    //     $file->filename = drupal_basename($uri);
                                    //     $file->filemime = file_get_mimetype($file->uri);
                                    //     $file->uid = $user->uid;
                                    //     $file->display = 1;
                                    //     $file->status = FILE_STATUS_PERMANENT;
                                    //     // If we are replacing an existing file re-use its database record.
                                    //     if ($replace == FILE_EXISTS_REPLACE) {
                                    //       $existing_files = file_load_multiple(array(), array('uri' => $uri));
                                    //       if (count($existing_files)) {
                                    //         $existing = reset($existing_files);
                                    //         $file->fid = $existing->fid;
                                    //         $file->filename = $existing->filename;
                                    //       }
                                    //     }
                                    //     // If we are renaming around an existing file (rather than a directory),
                                    //     // use its basename for the filename.
                                    //     elseif ($replace == FILE_EXISTS_RENAME && is_file($destination)) {
                                    //       $file->filename = drupal_basename($destination);
                                    //     }

                                    //     file_save($file);
                                    //     var_dump($file);
                                    //   }
                                }
                                catch (Exception $e) {
                                    throw new \Exception("File $rawValue coundn't be saved.");
                                }
                                break;

                            default:
                                $value = $rawValue;
                                break;
                        }
                    }
                }
            }
            if (empty($fieldMachineName)) {
                throw new \Exception("Entity property $fieldLabel doesn't exist.");
            }
            // $wrapper->$fieldMachineName = $value;
            // $wrapper->save();
        }
    }
}
