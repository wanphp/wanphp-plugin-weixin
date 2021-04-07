<?php

namespace Wanphp\Plugins\Weixin\Entities;

trait EntityTrait
{
  /**
   * 初始化实体
   * @param array $array
   */
  public function __construct(array $array)
  {
    foreach (array_intersect_key($array, $this->jsonSerialize()) as $key => $value) {
      $this->{$key} = $value;
    }
  }

  /**
   * @param $name
   * @param $arguments
   */
  public function __call($name, $arguments)
  {
    $action = substr($name, 0, 3);
    $property = strtolower(substr($name, 3));
    if ($action == 'set' && property_exists($this, $property)) {
      $this->{$property} = $arguments;
    }
  }

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }
}
