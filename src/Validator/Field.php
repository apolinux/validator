<?php 

namespace Apolinux\Validator;

class Field{
  /**
   * @var string
   */
  public $name ;

  /**
   * @var Rule[]
   */
  public $rules  = [];

  /**
   * @var bool
   */
  public $ignore_defined ;

  public function __construct(string $name, Array $rules=[]){
    $this->name = $name ;
    $this->rules = $rules ;
  }
}