<?php

namespace Apolinux\Validator ;

use Closure ;

/**
 * Validates fields from input data
 * 
 * Validate fields from input data as array or object according to defined or custom rules.
 *
 * This rules included: defined, PHP functions, is_array, is_object, min,max, regex, 
 * closures, static methods.
 *
 * Example how rules are defined:
 * <code>
 * $rules = [
 *   'name'      => 'is_alnum' , // php function
 *   'address'   => 'defined' , // internal validator
 *   'user'      => [ 'regex' => '/\w+/' , 'is_int' => null , ...] ,//rules defined in array
 *   'phone'     => ['method' => '\namespace\subnamespace\Classname::validationMethod' ..]
 *   'fieldname5'=> 'defined|is_int|min:3|max:20', // several rules in one line separated by '|'
 *   ...
 * ]
 * </code>
 *
 */
class Validator{

    /**
     * @var array
     */
    private $rules = [];

    /**
     * Error list
     * @var array
     */
    private $errors  = [];

    /**
     * define rules to be validated
     *
     * The rules are like this
     * <pre>
     * $rules = [
     *   'fieldname1' => 'phpfunc' ,
     *   'fieldname2' => 'defined' ,
     *   'fieldname3' => [ 'regex' => 'Regular_expression' , 'is_int' => null , ...]
     *   'fieldname4' => ['method' => '\libs\dir1\Classname::customMethodValidation' ..]
     *   ...
     * ]
     * </pre>
     *
     * These are the rules:
     * - 'defined' : only check if field exists
     * - internals: min, max, compare numerically, example:  'min:3|max:10', can be used for arrays, to count items
     * - is_*: is_int, is_array, is_object, is_scalar : validate if value belongs to specified type
     * - regex : validate if value matches regular expresion, example: 'regex:/\d+/'
     * - method : validate using a static method: 'classname::method'
     *   the method must receive this parameters:
     *   - $input : input array of data to be validated
     *   - $field : field name to be validated
     *   - $message : passed by reference, contains message to show if validation fails
     * - closure : define a function with the following parameters:
     *   - $input : input array of data to be validated
     *   - $value : field value to be validated
     *   - $message : passed by reference, contains message to show if validation fails
     *
     * - The rules can be concatenated using '|' for example: 'defined|is_int|min:5'
     * or can be an array:  ['is_int','regex:/\w+/',...]
     * - if a rule different of 'defined' exists, is no necessary to add the 'defined' rule
     *
     * @param array|object $rules rules to be validated
     */
    public function __construct(array $rules) {
        $this->rules = $rules;
    }

    /**
     * validates input against rules defined in constructor
     *
     * stops on first error. If stop_validation_default is false, continues 
     * validating even if there are more errors after.
     * 
     * @param array|object $input
     * @return boolean true if validation is OK
     * @throws ValidatorException
     */
    public function validate($input,$stop_validation_default=true){
        $this->errors = [] ;

        $input = (array)$input ;
        $rule_list = [] ;
        foreach($this->rules as $field => $rule_field){
            if(is_array($rule_field)){
                $rule_list[$field] =  $this->getRulesArray($field, $rule_field);
            }else {
                $rule_list[$field] =  $this->getRulesPiped($field, $rule_field) ;
            }
        }
        try{
            foreach($rule_list as $field => $rules_field){
                $stop_validation= $stop_validation_default ;
                foreach($rules_field as $rule_info){
                    $this->validateRuleField($input, $rule_info, $stop_validation);
                }
            }
        }catch(ValidatorException | ValidatorDataException $e){
            return false ;
        }

        return (count($this->errors) == 0 );
    }

        
    /**
     * validate rules for field
     * 
     * call validation functions for a field
     *
     * @param  mixed $input
     * @param  mixed $rule_info
     * @param  mixed $stop_validation
     * @throws ValidatorException
     * @return void
     */
    public function validateRuleField($input, $rule_info, $stop_validation){
        list($fieldname, $rulename, $ruleparam) = $rule_info ;
        try{
            if($rulename instanceof Closure || is_object($rulename)){
                $this->callClosure($input, $fieldname, $rulename) ;
            }elseif('method' == (string)$rulename){
                $this->_validate_method($input,$fieldname,$ruleparam);
            }elseif(method_exists($this, 'validate_' . $rulename)){
                $this->{'validate_'.$rulename}($input,$fieldname, $ruleparam);
            }elseif(function_exists($rulename)){
                $this->_callfunc($rulename,$input, $fieldname,$ruleparam);
            }/*
            @TODO, this implies to have rules of each field as array ,
            so must change the way to print errors, because is an array of arrays...
            elseif($rulename == 'nostop'){
                $stop_validation = false ;
            }*/else{
                throw new ValidatorDataException("The rule '$rulename' does not have any function associated", $fieldname) ;
            }
        }catch(ValidatorDataException $e){
            $this->errors[$e->getFieldName()] = $e->getMessage();
            if($stop_validation){
                throw new ValidatorException($e->getMessage());
            }
        }
    }

    /**
     * @deprecated
     */
    public function validateOld($input){
        $this->errors = [] ;

        $input = (array)$input ;
        $rule_list = [] ;
        foreach($this->rules as $field => $rule1){
            if(is_array($rule1)){
                $rule_list = array_merge($rule_list, $this->getRulesArray($field, $rule1));
            }else {
                $rule_list = array_merge($rule_list, $this->getRulesPiped($field, $rule1)) ;
            }
        }
        try{
            foreach($rule_list as $rule_info){
                list($field, $rulename, $ruleparam) = $rule_info ;
                try{
                    if($rulename instanceof Closure || is_object($rulename)){
                        $this->callClosure($input, $field, $rulename) ;
                    }elseif('method' == (string)$rulename){
                        $this->_validate_method($input,$field,$ruleparam);
                    }elseif(method_exists($this, 'validate_' . $rulename)){
                        $this->{'validate_'.$rulename}($input,$field, $ruleparam);
                    }elseif(function_exists($rulename)){
                        $this->_callfunc($rulename,$input, $field,$ruleparam);
                    }else{
                        throw new ValidatorException("The rule '$rulename' does not have any function associated") ;
                    }
                }catch(ValidatorDataException $e){

                }
            }
        }catch(ValidatorException $e){
            $this->errors[] = $e->getMessage();
            return false ;
        }

        return true ;
    }
        
    /**
     * get rules from array
     *
     * @param  string $field
     * @param  array $rule1
     * @return array
     */
    private function getRulesArray($field, $rule1){
        $rule_list = [];
        foreach($rule1 as $rulename => $ruleparam){
            if(is_int($rulename)){
                $rule_list[] = [$field,$ruleparam, null];
            }else{
                $rule_list[] = [$field,$rulename, $ruleparam];
            }
        }
        return $rule_list ;
    }

    /**
     * separate rules from string piped
     * 
     * @param  string $field
     * @param  string $rule1
     * @return array
     */
    private function getRulesPiped($field, $rule1) {
        $rules_piped = $this->explodeSp('|',$rule1);
        $rule_list = [] ;
        foreach($rules_piped as $rule2){
            $name_param = $this->explodeSp(':',$rule2);
            $rulename1 = $name_param[0] ;
            if(count($name_param) > 1){
                $ruleparam1 = $name_param[1];
            }else{
                $ruleparam1 = null ;
            }
            $rule_list[] = [$field, $rulename1, $ruleparam1] ;
        }

        return $rule_list ;
    }

    /**
     * explode working with objects
     *
     * if 2nd field is an object it must to exclude from explode and generate an array
     * 
     * @param string $sep
     * @param mixed $object
     * @return array
     */
    private function explodeSp($sep, $object){
        if(is_object($object)){
            $out = [$object] ;
        }else{
            $out = explode($sep, $object) ;
        }
        return $out ;
    }

    /**
     * validate if field is defined in array
     * @param array $input
     * @param mixed $field
     * @throws ValidatorException
     */
    private function validate_defined($input,$fieldname){
        if(!array_key_exists($fieldname, $input)){
            throw new ValidatorDataException("The field '$fieldname' is not defined", $fieldname);
        }
    }

    /**
     * validate if field is greater than value
     *
     * @param  array  $input
     * @param  string $fieldname
     * @param  array  $params
     * @return void
     * @throws ValidatorException
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
     * @throws ValidatorException
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
     * @throws ValidatorException
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
     * @throws ValidatorException
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
     * @throws ValidatorException
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
     * @throws ValidatorException
     */
    private function validate_regex($input, $fieldname, $ruleparam){
        $this->validate_defined($input, $fieldname);
        $this->validate_is_scalar($input, $fieldname);
        if(! preg_match($ruleparam,$input[$fieldname])){
            throw new ValidatorDataException("The field '$fieldname' not match regex '$ruleparam'", $fieldname);
        }
    }

    /**
     * validate with method in class
     * 
     * the method must be called statically
     * method must be defined in form  ClassName::MethodName
     * the parameters received are:
     * - input array
     * - fieldname
     * - message to show if fails validation
     *
     * @param array $input parameters to validate
     * @param string $field fieldname of input
     * @param string $ruleparam must be in form "classname::methodname"
     */
    private function _validate_method($input, $fieldname, $ruleparam){
        $this->validate_defined($input, $fieldname);
        list($class , $method) = explode('::',$ruleparam) ;
        $this->_validate_one($fieldname, class_exists($class) ,"The class '$class' does not exists") ;

        $this->_validate_one($fieldname, method_exists($class,$method), "The method '$method' of class '$class' does not exists") ;

        $result = $class::$method($input,$fieldname, $msg);
        $this->_validate_one($fieldname, $result, $msg ?? "The field '$fieldname' does not pass validator '$ruleparam'") ;
    }

    /**
     * validate expression
     * 
     * @param string $fieldname
     * @param bool $expression
     * @param string $msg
     * @throws ValidatorException
     */
    private function _validate_one($fieldname, $expression,$msg){
        if(! $expression){
            throw new ValidatorDataException($msg, $fieldname);
        }
    }

    /**
     * call php or custom external function
     * 
     * @param string $rulename
     * @param array $input
     * @param string $fieldname
     * @param string $ruleparam
     */
    private function _callfunc($rulename,$input, $fieldname,$ruleparam){
        $this->validate_defined($input, $fieldname);

        set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) use ($fieldname){
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }

            throw new ValidatorDataException($errstr, $fieldname);
        });

        if(is_null($ruleparam)){
            $this->_validate_one($fieldname, $rulename($input[$fieldname]), "The field '$fieldname' does not pass '$rulename' validator") ;
        }else{
            $this->_validate_one($fieldname, $rulename($input[$fieldname],$ruleparam), "The field '$fieldname' does not pass '$rulename' validator") ;
        }

        restore_error_handler();
    }

    /**
     * validate with closure
     *
     * closure to be called must be of form:
     * function($input, $value, &$message)
     * where:
     * - $input is input data array
     * - $value is value to validate
     * - $message is the message to show when fails validation
     *
     * @param array $input input data to validate
     * @param string $fieldname fieldname
     * @param Closure $rulename closure to be called
     */
    private function callClosure($input, $fieldname, Closure $rulename){
        $this->validate_defined($input, $fieldname);
        $result = $rulename($input,$input[$fieldname], $msg);
        $this->_validate_one($fieldname, $result, $msg ?? "The field '$fieldname' does not pass closure validator") ;
    }

    /**
     * get last error
     * @return string
     */
    public function getLastError(){
        if(count($this->errors) > 0){
            return array_values(array_slice($this->errors, -1))[0];
        }
    }

    /**
     * get first error
     * 
     * @return string
     */
    public function getFirstError(){
        return $this->errors[0] ?? '' ;
    }

    /**
     * return list of errors 
     * 
     * @return array
     */
    public function getErrors() : array{
        return $this->errors ;
    }
}
