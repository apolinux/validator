<?php 

namespace Apolinux\Validator;

class Rule{
  /**
   * @var Field
   */
  public $field ;

  /**
   * @var string|Callable
   */
  public $name ;

  /**
   * @var mixed
   */
  public $parameter;

  public function __construct(Field $field, $name, $parameter=null){
    $this->field = $field ;
    $this->name = $name ;
    $this->parameter = $parameter;
  }
}