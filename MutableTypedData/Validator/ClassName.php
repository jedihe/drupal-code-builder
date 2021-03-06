<?php

namespace DrupalCodeBuilder\MutableTypedData\Validator;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Validator\ValidatorInterface;

/**
 * Validates a PHP class name.
 */
class ClassName implements ValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(DataItem $data): bool {
    return preg_match('@^([[:upper:]][[:lower:]]+)+$@', $data->value);
  }

  /**
   * {@inheritdoc}
   */
  public function message(DataItem $data): string {
    return "The @label must be a PHP class name in PascalCase format.";
  }

}
