<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for module permissions on Drupal 8.
 */
class Permission extends BaseGenerator {

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + array(
      'permission' => array(
        'label' => 'Permission machine-readable name',
        'default' => 'access my_module',
        'required' => TRUE,
      ),
      'title' => PropertyDefinition::create('string')
        ->setLabel('Permission human-readable name')
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
          ->setExpression("machineToLabel(get('..:permission'))")
            ->setDependencies('..:permission')
        ),
      'description' => PropertyDefinition::create('string')
        ->setLabel('Permission description')
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("get('..:title')")
            ->setDependencies('..:title')
        ),
      'restrict_access' => array(
        'label' => 'Access warning',
        'description' => 'Whether the permission should show a warning that it should be granted with care.',
        'default' => FALSE,
        'format' => 'boolean',
      ),
    );
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = array(
      '%module.permissions.yml' => array(
        'component_type' => 'YMLFile',
        'filename' => '%module.permissions.yml',
      ),
    );

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:%module.permissions.yml';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $permission_name = $this->component_data['permission'];

    $permission_info = array(
      'title' => $this->component_data['title'],
      'description' => $this->component_data['description'],
    );
    if (!empty($this->component_data['restrict_access'])) {
      $permission_info['restrict access'] = TRUE;
    }

    $yaml_data[$permission_name] = $permission_info;

    return [
      'permission' => [
        'role' => 'yaml',
        'content' => $yaml_data,
      ],
    ];
  }

}
