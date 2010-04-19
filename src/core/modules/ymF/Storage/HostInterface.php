<?php

/**
 * Storage host interface
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF\Storage;

interface HostInterface
{
  /**
   * Called when field is changed
   *
   * If returns true, field is set to new value,
   * otherwise, old value is kept
   *
   * @param Storage $storage
   * @param string $name
   * @param mixed $value
   * @return boolean
   */
  public function validateField(Storage $storage, $name, $value);
}