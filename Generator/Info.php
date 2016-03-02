<?php

/**
 * @file
 * Contains generator classes for .info files.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator base class for module info file.
 */
class Info extends File {

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $files['info'] = array(
      'path' => '',
      'filename' => '%module.info',
      'body' => $this->file_body(),
      // We join the info lines with linebreaks, as they (currently!) do not
      // come with their own lineends.
      // TODO: fix this!
      'join_string' => "\n",
    );
    return $files;
  }

}
