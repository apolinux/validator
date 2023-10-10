<?php

namespace Apolinux\Validator;

class ValidatorDataException extends \Exception {
  private $field_name ;
  public function __construct($message, $field_name,$code=0){
    $this->field_name = $field_name ;
    parent::__construct($message, $code);
  }

  public function getFieldName(){
    return $this->field_name ;
  }
}

