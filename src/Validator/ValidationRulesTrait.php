<?php 

namespace Apolinux\Validator;

/**
 * List of validators
 * 
 */
trait ValidationRulesTrait{
  /**
     * validate if field is defined in array input
     * 
     * @param array $input
     * @param mixed $field
     * @throws ValidatorDataException
     */
    private function validate_defined($input,$fieldname){
      if(!array_key_exists($fieldname, $input)){
          throw new ValidatorDataException("The field '$fieldname' is not defined", $fieldname);
      }
  }

  /**
   * define that field is optional
   * 
   * if is not defined not raise errors. If is defined continues with validations. 
   * prefer to add at beggining of rule list for field
   * @param array $input
   * @param string $fieldname
   * @throws ValidatorBreakException announces a break in loop outside function
   */
  public function validate_optional($input, $fieldname){
    try{
      $this->validate_defined($input, $fieldname) ;
    }catch(ValidatorDataException $e){    
      throw new ValidatorBreakException() ;
    }
  }

  /**
   * validate if field is greater than value
   *
   * @param  array  $input
   * @param  string $fieldname
   * @param  array  $params
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_min($input,$fieldname,$params){
      $this->validate_defined($input, $fieldname);
      $value = $input[$fieldname];
      if(is_scalar($value)){
          if($value < $params){
              throw new ValidatorDataException("The field '$fieldname' has not the minimum length required", $fieldname);
          }
      }elseif(count($value) < $params){
          throw new ValidatorDataException("The field '$fieldname' has not the minimum length required", $fieldname);
      }
  }

  /**
   * validate if field is less than value
   *
   * @param  array  $input
   * @param  string $fieldname
   * @param  array  $params
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_max($input,$fieldname,$params){
      $this->validate_defined($input, $fieldname);
      $value = $input[$fieldname];
      if(is_scalar($value)){
          if($value > $params){
              throw new ValidatorDataException("The field '$fieldname' has not the maximum length required", $fieldname);
          }
      }elseif(count($value) < $params){
          throw new ValidatorDataException("The field '$fieldname' has not the maximum length required", $fieldname);
      }
  }

  /**
   * validate if field has minimum length
   *
   * @param  array  $input
   * @param  string $fieldname
   * @param  array  $params
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_minlength($input,$fieldname,$params){
      $this->validate_defined($input, $fieldname);
      $value = $input[$fieldname];
      
      if(! is_scalar($value)){
          throw new ValidatorDataException("The field '$fieldname' is not a scalar value", $fieldname);
      }

      if(strlen($value) < $params){
          throw new ValidatorDataException("The field '$fieldname' has not the minimum character length required", $fieldname);
      }
  }

  /**
   * validate if field has maximum length
   *
   * @param  array  $input
   * @param  string $fieldname
   * @param  array  $params
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_maxlength($input,$fieldname,$params){
      $this->validate_defined($input, $fieldname);
      $value = $input[$fieldname];
      if(! is_scalar($value)){
          throw new ValidatorDataException("The field '$fieldname' is not a scalar value", $fieldname);
      }
      if(strlen($value) > $params){
          throw new ValidatorDataException("The field '$fieldname' has not the maximum character length required", $fieldname);
      }
  }
  /**
   * validates if value is between two numbers
   * 
   * example : 'fieldname' => 'range:12.5,57.4'
   *
   * @param  array $input
   * @param  string $fieldname
   * @param  array $params
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_range($input,$fieldname,$params){
      $limits = $this->getLimitsRange($params);
      if(count($limits) < 2){
        throw new ValidatorDataException("The range defined: '$params' is not valid", $fieldname) ;
      }

      list($min, $max) = $limits ;

      $this->validate_defined($input, $fieldname);

      $value = $input[$fieldname];

      if(is_scalar($value)){
          if($value > $max || $value < $min){
              throw new ValidatorDataException("The field '$fieldname' exceeds the range limits defined", $fieldname);
          }
      }elseif(count($value) > $max || count($value) < $min){
          throw new ValidatorDataException("The field '$fieldname' exceeds the range limits defined", $fieldname);
      }
  }

  /**
   * get limits of a data range
   *
   * @param  array|string $params
   * @param  string $delimiter
   * @return array
   */
  private function getLimitsRange($params, $delimiter=','){
    if(is_array($params)){
      return $params ;
    }
    return explode($delimiter, $params);
  }

  /**
   * validate if field is an integer
   *
   * @param  array $input
   * @param  string $fieldname
   * @return void
   */
  private function validate_is_int($input,$fieldname){
      $this->validate_defined($input, $fieldname);
      $value = $input[$fieldname];
      if(! preg_match('/^\d+$/',$value)){
              throw new ValidatorDataException("The field '$fieldname' is not an integer", $fieldname);
      }
  }

  /**
   * validate if field is an array
   *
   * @param  array $input
   * @param  string $fieldname
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_is_array($input,$fieldname){
      $this->validate_defined($input, $fieldname);
      $value = $input[$fieldname];
      if(! is_array($value)){
              throw new ValidatorDataException("The field '$fieldname' is not an array", $fieldname);
      }
  }
  
  /**
   * validate if field is an object
   *
   * @param  array $input
   * @param  string $fieldname
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_is_object($input,$fieldname){
      $this->validate_defined($input, $fieldname);
      $value = $input[$fieldname];
      if(! is_object($value)){
              throw new ValidatorDataException("The field '$fieldname' is not an object", $fieldname);
      }
  }

  /**
   * rule to validate is a scalar
   * 
   * scalar means is int, float, string or boolean value.
   *
   * @param  array $input
   * @param  string $fieldname
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_is_scalar($input,$fieldname){
      $this->validate_defined($input, $fieldname);
      $value = $input[$fieldname];
      if(! is_scalar($value)){
              throw new ValidatorDataException("The field '$fieldname' is not an scalar", $fieldname);
      }
  }

  /**
   * validates if field value match regular expression
   *
   * @param array $input
   * @param string $field
   * @param string $ruleparam
   * @throws ValidatorDataException
   */
  private function validate_regex($input, $fieldname, $ruleparam){
      $this->validate_defined($input, $fieldname);
      $this->validate_is_scalar($input, $fieldname);
      if(! preg_match($ruleparam,$input[$fieldname])){
          throw new ValidatorDataException("The field '$fieldname' not match regex '$ruleparam'", $fieldname);
      }
  }

  /**
   * passes if field is positive
   * 
   * @param  array $input
   * @param  string $fieldname
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_is_positive($input,$fieldname){
    $this->validate_defined($input, $fieldname);
    $value = $input[$fieldname];
    $this->validate_is_int($input, $fieldname);

    if($value < 0){
      throw new ValidatorDataException("The field '$fieldname' is not positive", $fieldname);
    }
  }

  /**
   * passes if field is negative
   * 
   * @param  array $input
   * @param  string $fieldname
   * @return void
   * @throws ValidatorDataException
   */
  private function validate_is_negative($input,$fieldname){
    $this->validate_defined($input, $fieldname);
    $value = $input[$fieldname];
    $this->validate_is_int($input, $fieldname);

    if($value >= 0){
      throw new ValidatorDataException("The field '$fieldname' is not negative", $fieldname);
    }
  }

  /**
   * passes if field is a valid email address
   * 
   * @param  array $input
   * @param  string $fieldname
   * @return void
   * @throws ValidatorDataException
   */
  /*private function validate_is_email($input,$fieldname){
    $this->validate_defined($input, $fieldname);
    $value = $input[$fieldname];

    if($value >= 0){
      throw new ValidatorDataException("The field '$fieldname' is not negative", $fieldname);
    }
  }
  is_url : passes if field is a valid URL
  */
}