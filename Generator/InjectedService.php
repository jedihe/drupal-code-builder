<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use CaseConverter\StringAssembler;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a service injection into a class.
 */
class InjectedService extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition['service_id'] = [
      'label' => 'Service name',
      'required' => TRUE,
    ];

    $data_definition['service_info'] = PropertyDefinition::create('mapping')
      ->setDefault(DefaultDefinition::create()
        ->setCallable([static::class, 'defaultServiceInfo'])
        ->setDependencies('..:service_id')
    );

    // Bit of a hack for PHPUnitTest generator's sake. Lets the requesting
    // generator tack a suffix onto the roles we give to component contents.
    // PHPUnitTest needs this as it has two kinds of service.
    $data_definition['role_suffix'] = [
      'internal' => TRUE,
    ];

    return $data_definition;
  }

  public static function defaultServiceInfo($data_item) {
    $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $services_data = $task_handler_report_services->listServiceData();

    // Build up the service info.
    $service_info = [];
    $service_info['id'] = $service_id = $data_item->getParent()->service_id->value;
    $service_info['type']         = 'service';

    // Copy these explicitly for maintainability and readability.
    $service_info['label']          = $services_data[$service_id]['label'];
    $service_info['variable_name']  = $services_data[$service_id]['variable_name'];
    $service_info['description']    = $services_data[$service_id]['description'];
    $service_info['interface']      = $services_data[$service_id]['interface'];
    $service_info['class']          = $services_data[$service_id]['class'] ?? '';
    $service_info['property_name']  = CaseString::snake($service_info['variable_name'])->camel();

    if (substr_count($service_id, ':') != 0) {
      // If the service name contains a ':' then it's a pseudoservice, that
      // is, not an actual service but something else injectable.
      [$pseudo_service_type, $variant] = explode(':', $service_id);

      $service_info['type']           = 'pseudoservice';
      $service_info['variant']        = $variant;
      $service_info['real_service']   = $services_data[$service_id]['real_service'];
      $service_info['service_method'] = $services_data[$service_id]['service_method'];

      $real_service_info = $services_data[$services_data[$service_id]['real_service']];
      $service_info['real_service_variable_name'] = $real_service_info['variable_name'];
    }

    // If the service has no interface, typehint on the class.
    $service_info['typehint'] = $service_info['interface'] ?: $service_info['class'];

    return $service_info;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $service_info = $this->component_data['service_info'];

    $service_type = (substr_count($service_info['id'], ':') == 0) ? 'service' : 'pseudoservice';

    if ($service_info['type'] == 'service') {
      $container_extraction = "\$container->get('{$service_info['id']}'),";

      $property_assignment = [
        'id'            => $service_info['id'],
        'property_name' => $service_info['property_name'],
        'variable_name' => $service_info['variable_name'],
      ];
    }
    else {
      // Pseudoservice: needs to be extracted from a real service.
      $container_extraction = "\$container->get('{$service_info['real_service']}')->{$service_info['service_method']}('{$service_info['variant']}'),";

      $property_assignment = [
        'id'            => $service_info['id'],
        'property_name' => $service_info['property_name'],
        'variable_name' => $service_info['variable_name'],
        'parameter_extraction' => "{$service_info['real_service_variable_name']}->{$service_info['service_method']}('{$service_info['variant']}')",
      ];
    }

    $contents = [
      'service' => [
        'role' => 'service',
        'content' => $service_info,
      ],
      'service_property' => [
        'role' => 'service_property',
        'content' => [
          'id'            => $service_info['id'],
          'property_name' => $service_info['property_name'],
          'typehint'      => $service_info['typehint'],
          'description'   => $service_info['description'],
        ],
      ],
      'container_extraction' => [
        'role' => 'container_extraction',
        'content' => $container_extraction,
      ],
      'constructor_param' => [
        'role' => 'constructor_param',
        'content' => [
          'id'          => $service_info['id'],
          'name'        => $service_info['variable_name'],
          'typehint'    => $service_info['typehint'],
          'description' => $service_info['description'] . '.',
          'type'        => $service_type,
          // 'container_extraction' => $container_extraction,
        ],
      ],
      'property_assignment' => [
        'role' => 'property_assignment',
        'content' => $property_assignment,
      ],
    ];

    if (!empty($this->component_data['role_suffix'])) {
      foreach ($contents as &$data) {
        $data['role'] .= $this->component_data['role_suffix'];
      }
    }

    return $contents;
  }

}
