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
 * 
 * rules defined:
 * - defined  : passes if field is defined
 * - optional : passes even if field does not exist
 * - min:value : passes if field is greater than value       
 * - max:value : passes if field is less than value
 * - minlength:value : passes if length field is greater than value
 * - maxlength:value : passes if length field is lesser than value
 * - range:min,max     : passes if field is between min and max value
 * - is_int  : passes if field is integer
 * - is_array : passes if field is an array
 * - is_object : passes if field is an object
 * - is_scalar : passes if field is a scalar, it means string o number, not object, array or other PHP resource
 * - is_regex:regex  : passes if field match regex value
 * - is_positive : passes if field is positive
 * - is_negative : passes if field is negative
 * - is_email : passes if field is a valid email address(not yet)
 * - is_url : passes if field is a valid URL (not yet)
 * ]
 * </code>
 *
 */
class Validator{

    use ValidationRulesTrait ;
    
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
     * @param bool $stop_validation_default. default to true
     * @return boolean true if validation is OK
     */
    public function validate($input,$stop_validation_default=true){
        $this->errors = [] ;

        $input = (array)$input ;
        $field_list = [];
        foreach($this->rules as $field => $rule_field){
            $field_object = new Field($field);
            if(is_array($rule_field)){
                $field_object->rules = $this->getRulesArray($field_object, $rule_field);
            }else {
                $field_object->rules = $this->getRulesPiped($field_object, $rule_field) ;
            }
            $field_list[] = $field_object;
        }
        try{
            foreach($field_list as $field_object){
                $stop_validation= $stop_validation_default ;
                foreach($field_object->rules as $rule_info){
                    
                    try{
                        $this->validateRuleField($input, $rule_info, $stop_validation);
                    }catch(ValidatorBreakException $e){
                        break ;
                    }
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
     * @param  array $input data to be validated
     * @param  Rule $rule validation rule
     * @param  bool $stop_validation
     * @throws ValidatorDataException when a validator fails
     * @throws ValidatorException when any validator fails
     * @return void
     */
    public function validateRuleField($input, Rule $rule, $stop_validation){
        $fieldname = $rule->field->name;
        $rulename = $rule->name ;
        $ruleparam = $rule->parameter ;

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
     * get rules from array
     *
     * @param  Field $field
     * @param  array $rule1
     * @return array
     */
    private function getRulesArray(Field $field, $rule1){
        $rule_list = [];
        foreach($rule1 as $rulename => $ruleparam){
            if(is_int($rulename)){
                $rule_list[] = new Rule($field, $ruleparam, null);
            }else{
                $rule_list[] = new Rule($field, $rulename, $ruleparam);
            }
        }
        return $rule_list ;
    }

    /**
     * separate rules from string piped
     * 
     * @param  Field $field
     * @param  string $rule1
     * @return array
     */
    private function getRulesPiped(Field $field, $rule1) {
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
            $rule_list[] = new Rule($field, $rulename1, $ruleparam1);
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
     * @throws ValidatorDataException
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
     * @throws ValidatorDataException
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
