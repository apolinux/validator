# Validator

Validates input fields from an array or object according to defined or custom rules.


This rules included: defined, PHP functions, is_array, is_object, min,max, regex, closures, static methods.

The rules can be concatenated by '|' character or using an array list, example:
'rule1|rule2|rule3 ...'  or ['rule1', 'rule2', 'rule3',...]

## Example

```
  <?php
  
  $rules = [
   'name'      => 'is_alnum' , // php function
   'address'   => 'defined' , // internal validator
   'user'      => [ 'regex' => '/\w+/' , 'is_int' => null , ...] ,//rules defined in array
   'phone'     => ['method' => '\namespace\subnamespace\Classname::validationMethod' ..]
   'fieldname5'=> 'defined|is_int|min:3|max:20', // several rules in one line separated by '|'
   ...
  ]

  $validator = new Apolinux\Validator ;
  if( ! $validator->validate($rules) ){
    throw new \Exception('There is an error with input:' . $validator->getLastError()) ;
  }
```

## rules

List of rules

* defined   : validate if field is defined
* min       : validate if number is greater than minimum value or if array has more items than minimum
* max       : validate if number is less than maximum value or if array has less items than maximum
* minlength : validate if character length is greater than value
* maxlength : validate if character length is fewer than value
* range     : validate if field is between a range of float numbers
* is_int    : validate if number is integer
* is_array  : validate if field is an array
* is_object : validate if field is an object
* is_scalar : validate if number is scalar using is_scalar PHP function
* regex     : validate if field match regex. must include enclosing characters like '/'
* method    : validate using a static method. The method must return a boolean value

### Static method

The method is defined like this

```
$rules = [
  'field_name' => ['method' => 'Apolinux\CustomNamespace\ValidatorField::validate', ...]
]
```

The static method must receive three parameters:
* the input data array
* the value of field
* the text message to show if validation failed. must be passed by reference

And return true if validation pass.


besides the functions defined above, there are other ways to validate using functions and callable:

### callables  

validate using an anonymous function. As static method is defined, The function must receive three parameters:
* the input data array
* the value of field
* the text message to show if validation failed. must be passed by reference

And return true if validation pass.

Example

```
$rules = [
  'name' => function($input, $value, &$message){
    //validate data 
    if(validation_failed){
      return false ;
    }
    // no error in validation
    return true ;
  },
  ...
] ;
```


function  : uses a existent function to make the validation

```
$rules = [
  'field_name1' => 'is_double' ,  // PHP function 
  'field_name2' => 'user_defined_function', // custom function
  ...
] ;

```

## Validating all

By default the validator stops on first error. If it's required to show all errors,
run validate() method with second parameter to false:


$validator->validate($rules, false) ;


To get all errors call getErrors() method.


## TODO

* add support to multiple languages
* add more rules: equal length, is_json, is_xml,etc

