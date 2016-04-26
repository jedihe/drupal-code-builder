<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Plugin.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a plugin.
 */
class Plugin extends PHPFile {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A Plugin generator should use as its name the part of the plugin manager
   * service name after 'plugin.manager.'
   * TODO: change this so we can generate more than one plugin of a particular
   * type at a time!
   */
  public $name;

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values. Valid
   *   properties are:
   *    - 'class': The name of the annotation class that defines the plugin
   *      type, e.g. 'Drupal\Core\Entity\Annotation\EntityType'.
   *      TODO: since the classnames are unique regardless of namespace, figure
   *      out if there is a way of just specifying the classname.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Set some default properties.
    $component_data += array();

    $plugin_type = $component_data['plugin_type'];

    $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $plugin_data = $mb_task_handler_report_plugins->listPluginData();
    $plugin_data = $plugin_data[$plugin_type];

    $component_data['plugin_type_data'] = $plugin_data;

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

  /**
   * Define the component data this component needs to function.
   */
  protected static function componentDataDefinition() {
    return array(
      'plugin_type' => array(
        'label' => 'Plugin type',
        'required' => TRUE,
        'options' => function(&$property_info) {
          $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');

          $options = $mb_task_handler_report_plugins->listPluginNamesOptions();

          return $options;
        },
      ),
      'plugin_name' => array(
        'label' => 'Plugin name',
        'required' => TRUE,
        // NOT WORKING!
        'Xdefault' => function($component_data) {
          return $component_data['root_name'] . 'PANTS';
        },
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function requestedComponentHandling() {
    return 'repeat';
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    // TODO: can these be set up in the constructor? -- but we don't have access
    // to the base component property yet.
    // Create a class name.
    // TODO: allow this to be set in the component data.
    $this->component_data['class_name'] = $this->root_component->component_data['camel_case_name']
      . ucfirst($this->component_data['plugin_name']);

    $this->component_data['namespace'] = implode('\\', array(
      'Drupal',
      $this->root_component->component_data['root_name'],
      $this->pathToNamespace($this->component_data['plugin_type_data']['subdir']),
    ));

    // Get the path for this plugin from the plugin type data.
    $this->path = 'src/' . $this->component_data['plugin_type_data']['subdir'];

    // Create a filename
    $this->filename = $this->component_data['class_name'] . '.php';

    $files[$this->filename] = array(
      // TODO: revisit
      'path' => $this->path,
      'filename' => $this->filename,
      'body' => $this->file_contents(),
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
    );
    return $files;
  }

  /**
   * Return the summary line for the file docblock.
   */
  function file_doc_summary() {
    return "Contains \\" . $this->component_data['namespace'] . '\\' . $this->component_data['class_name'];
  }

  /**
   * Return the main body of the file code.
   *
   * TODO: messier and messier arrays within arrays! file_contents() needs
   * rewriting!
   */
  function code_body() {
    return array_merge(
      array($this->code_namespace()),
      $this->class_annotation(),
      array($this->class_body())
    );
  }

  /**
   * Produces the namespace and 'use' lines.
   */
  function code_namespace() {
    $code = array();

    $code[] = 'namespace ' . $this->component_data['namespace'] . ';';
    $code[] = '';
    // TODO!!! is there any way to figure these out??
    $code[] = '// use yadayada;';
    $code[] = '';

    return implode("\n", $code);
  }

  /**
   * Produces the plugin class annotation.
   */
  function class_annotation() {
    $annotation_variables = $this->component_data['plugin_type_data']['plugin_properties'];
    //ddpr($class_variables);

    // Drupal\Core\Block\Annotation\Block

    $annotation_class_path = explode('\\', $this->component_data['plugin_type_data']['plugin_definition_annotation_name']);
    $annotation_class = array_pop($annotation_class_path);

    $docblock_code = array();
    $docblock_code[] = '@' . $annotation_class . '(';

    foreach ($annotation_variables as $annotation_variable => $annotation_variable_info) {
      if ($annotation_variable == 'id') {
        $docblock_code[] = '  ' . $annotation_variable . ' = "' . $this->component_data['plugin_name'] . '",';
        continue;
      }

      if ($annotation_variable_info['type'] == '\Drupal\Core\Annotation\Translation') {
        // The annotation property value is translated.
        $docblock_code[] = '  ' . $annotation_variable . ' = @Translation("TODO: replace this with a value"),';
        continue;
      }

      // It's a plain string.
      $docblock_code[] = '  ' . $annotation_variable . ' = "TODO: replace this with a value",';
    }
    $docblock_code[] = ')';

    return $this->docBlock($docblock_code);
  }

  /**
   * Produce the class.
   */
  function class_body() {
    $code = array();

    foreach ($this->component_data['plugin_type_data']['plugin_interface_methods'] as $interface_method_name => $interface_method_data) {
      $function_doc = $this->docBlock('{@inheritdoc}');
      $code = array_merge($code, $function_doc);

      // Trim the semicolon from the end of the interface method.
      $method_declaration = substr($interface_method_data['declaration'], 0, -1);

      $code[] = "$method_declaration {";
      // Add a comment with the method's first line of docblock, so the user
      // has something more informative than '{@inheritdoc}' to go on!
      $code[] = '  // ' . $interface_method_data['description'];
      $code[] = '}';
      $code[] = '';
    }

    // Indent all the class code.
    // TODO: is there a nice way of doing indents?
    $code = array_map(function ($line) {
      return empty($line) ? $line : '  ' . $line;
    }, $code);

    // Add the top and bottom.
    // Urgh, going backwards! Improve DX here!
    array_unshift($code, '');
    array_unshift($code, 'class ' . $this->component_data['class_name'] . ' {');

    $code[] = '}';
    // Newline at end of file. TODO: this should be automatic!
    $code[] = '';

    return implode("\n", $code);
  }

  /**
   * TODO: is there a core function for this?
   */
  function pathToNamespace($path) {
    return str_replace('/', '\\', $path);
  }

}
