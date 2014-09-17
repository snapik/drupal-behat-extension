<?php
namespace Promet\Drupal\Behat\SubContext;

use Promet\Drupal\Behat\SubContext;

class ContentTypeContext extends SubContext
{
    public $contentType;

    public static function getAlias() {
        return "DrupalContentType";
    }
    /**
     * @Given /^"([^"]*)" Content Type exists$/
     */
    public function ContentTypeExists($type) {
        $types = node_type_get_types();
        foreach ($types as $contentType) {
           if ($contentType->name == $type) {
                $this->contentType = $contentType;
                break;
           }
        }
        assertNotEmpty($this->contentType);
    }
    /**
     * @Then /^I should have a "([^"]*)" field as a "([^"]*)" type, has a "([^"]*)" widget, (not required|required), and allows (\d+) value[s]?[.]?$/
     */
    public function iShouldHaveAFieldAsTypeHasAWidgetRequiredAndAllowsValue($name, $type, $widget, $required, $cardinality) {
        $fields = field_info_instances("node", $this->contentType->type);
        $wantedField = NULL;
        foreach ($fields as $field) {
            if ($field['label'] == $name) {
                $wantedField = $field;
            }
        }
        assertNotEmpty($wantedField, "Field with the label $name doesn't exist");
        $fieldInfo = field_info_field($wantedField['field_name']);
        $widgetInfo = field_info_widget_types();
        $fieldTypeInfo = field_info_field_types();
        $wantedField['widget']['info'] = $widgetInfo[$wantedField['widget']['type']];
        $wantedField['type'] = $fieldTypeInfo[$fieldInfo['type']];
        assertEquals(
            $type,
            $wantedField['type']['label'],
            "$name doesn't have the type $type. Instead it has " . $wantedField['type']['label'] . '.'
        );
        assertEquals(
            $widget,
            $wantedField['widget']['info']['label'],
            "$name doesn't have the widget type $widget. Instead it has " . $wantedField['widget']['info']['label'] . '.'
        );
        $fieldRequired = $wantedField['required'] ? 'required' : 'not required';
        assertEquals(
            $required,
            $fieldRequired,
            "$name is marked '$fieldRequired'. It should be '$required'."
        );
        assertEquals(
            $cardinality,
            $fieldInfo['cardinality'],
            "$name allows " . $fieldInfo['cardinality'] . " values. It should only allow $cardinality values."
        );
    }
}