# Validator

Validates input fields from an array or object according to defined or custom rules.


This rules included: defined, PHP functions, is_array, is_object, min,max, regex, closures, static methods

# Example

Example how rules are defined:

````
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
````