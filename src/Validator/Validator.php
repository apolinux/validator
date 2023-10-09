<?php

namespace Apolinux\Validator ;

use Closure ;

/**
 * validates input fields from an array or object
 * according to defined or custom rules.
 *
 * this rules included: defined, PHP functions, is_array, is_object, min,max, regex, closures, static methods.
 *
 * Example how rules are defined:
 * <pre>
 * $rules = [
 *   'name'      => 'is_alnum' , // php function
 *   'address'   => 'defined' , // internal validator
 *   'user'      => [ 'regex' => '/\w+/' , 'is_int' => null , ...] ,//rules defined in array
 *   'phone'     => ['method' => '\namespace\subnamespace\Classname::validationMethod' ..]
 *   'fieldname5'=> 'defined|is_int|min:3|max:20', // several rules in one line separated by '|'
 *   ...
 * ]
 * </pre>
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
    public function __construct($rules) {
        $this->rules = (array)$rules;
    }

    /**
     * validates against rules defined in constructor
     *
     * @param array $input1
     * @return boolean true if validation is OK
     * @throws \Exception
     */
    public function validate($input1){
        $this->errors = [] ;

        $input = (array)$input1 ;
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
            }
        }catch(ValidatorException $e){
            $this->errors[] = $e->getMessage();
            return false ;
        }

        return true ;
    }

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
     * @param type $input
     * @param type $field
     * @throws ValidatorException
     */
    private function validate_defined($input,$field){
        if(!array_key_exists($field, $input)){
            throw new ValidatorException("The field '$field' is not defined");
        }
    }

    private function validate_min($input,$fieldname,$params){
        $this->validate_defined($input, $fieldname);
        $value = $input[$fieldname];
        if(is_scalar($value)){
            if($value < $params){
                throw new ValidatorException("The field '$fieldname' has not the minimum length required");
            }
        }elseif(count($value) < $params){
            throw new ValidatorException("The field '$fieldname' has not the minimum length required");
        }
    }

    private function validate_max($input,$fieldname,$params){
        $this->validate_defined($input, $fieldname);
        $value = $input[$fieldname];
        if(is_scalar($value)){
            if($value > $params){
                throw new ValidatorException("The field '$fieldname' has not the maximum length required");
            }
        }elseif(count($value) < $params){
            throw new ValidatorException("The field '$fieldname' has not the maximum length required");
        }
    }

    private function validate_minlength($input,$fieldname,$params){
        $this->validate_defined($input, $fieldname);
        $value = $input[$fieldname];
        if(strlen($value) < $params){
            throw new ValidatorException("The field '$fieldname' has not the minimum character length required");
        }
    }

    private function validate_maxlength($input,$fieldname,$params){
        $this->validate_defined($input, $fieldname);
        $value = $input[$fieldname];
        if(strlen($value) > $params){
            throw new ValidatorException("The field '$fieldname' has not the maximum character length required");
        }
    }
    /**
     * validates if value is between two numbers
     * example : 'fieldname' => 'range:12.5,57.4'
     */
    private function validate_range($input,$fieldname,$params){
        $limits = $this->getLimitsRange($params);
        if(count($limits) < 2){
          throw new ValidatorException("The range defined: '$params' is not valid") ;
        }

        list($min, $max) = $limits ;

        $this->validate_defined($input, $fieldname);

        $value = $input[$fieldname];

        if(is_scalar($value)){
            if($value > $max || $value < $min){
                throw new ValidatorException("The field '$fieldname' exceeds the range limits defined");
            }
        }elseif(count($value) > $max || count($value) < $min){
            throw new ValidatorException("The field '$fieldname' exceeds the range limits defined");
        }
    }

    private function getLimitsRange($params, $delimiter=','){
      if(is_array($params)){
        return $params ;
      }
      return explode($delimiter, $params);
    }

    private function validate_is_int($input,$fieldname){
        $this->validate_defined($input, $fieldname);
        $value = $input[$fieldname];
        if(! preg_match('/^\d+$/',$value)){
                throw new ValidatorException("The field '$fieldname' is not an integer");
        }
    }

    private function validate_is_array($input,$fieldname){
        $this->validate_defined($input, $fieldname);
        $value = $input[$fieldname];
        if(! is_array($value)){
                throw new ValidatorException("The field '$fieldname' is not an array");
        }
    }

    private function validate_is_object($input,$fieldname){
        $this->validate_defined($input, $fieldname);
        $value = $input[$fieldname];
        if(! is_object($value)){
                throw new ValidatorException("The field '$fieldname' is not an object");
        }
    }

    private function validate_is_scalar($input,$fieldname){
        $this->validate_defined($input, $fieldname);
        $value = $input[$fieldname];
        if(! is_scalar($value)){
                throw new ValidatorException("The field '$fieldname' is not an scalar");
        }
    }

    /**
     * validates if field value match regular expression
     *
     * @param type $input
     * @param type $field
     * @param type $ruleparam
     * @throws ValidatorException
     */
    private function validate_regex($input, $field, $ruleparam){
        $this->validate_defined($input, $field);
        $this->validate_is_scalar($input, $field);
        if(! preg_match($ruleparam,$input[$field])){
            throw new ValidatorException("The field '$field' not match regex '$ruleparam'");
        }
    }

    /**
     * validate with method in class
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
    private function _validate_method($input, $field, $ruleparam){
        $this->validate_defined($input, $field);
        list($class , $method) = explode('::',$ruleparam) ;
        $this->_validate_one(class_exists($class) ,"The class '$class' does not exists") ;

        $this->_validate_one(method_exists($class,$method), "The method '$method' of class '$class' does not exists") ;

        $result = $class::$method($input,$field, $msg);
        $this->_validate_one( $result, $msg ?? "The field '$field' does not pass validator '$ruleparam'") ;
    }

    /**
     * validate expression
     * @param bool $expression
     * @param string $msg
     * @throws ValidatorException
     */
    private function _validate_one($expression,$msg){
        if(! $expression){
            throw new ValidatorException($msg);
        }
    }

    /**
     * call php or custom external function
     * @param string $rulename
     * @param array $input
     * @param string $field
     * @param string $ruleparam
     */
    private function _callfunc($rulename,$input, $field,$ruleparam){
        $this->validate_defined($input, $field);

        set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }

            throw new ValidatorException($errstr, 0);
        });

        if(is_null($ruleparam)){
            $this->_validate_one( $rulename($input[$field]), "The field '$field' does not pass '$rulename' validator") ;
        }else{
            $this->_validate_one($rulename($input[$field],$ruleparam), "The field '$field' does not pass '$rulename' validator") ;
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
     * @param string $field fieldname
     * @param Closure $rulename closure to be called
     */
    private function callClosure($input, $field, Closure $rulename){
        $this->validate_defined($input, $field);
        $result = $rulename($input,$input[$field], $msg);
        $this->_validate_one( $result, $msg ?? "The field '$field' does not pass closure validator") ;
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
}
