<?php

use Apolinux\Validator\Validator;
use Apolinux\Validator\ValidatorException;
use PHPUnit\Framework\TestCase as PHPUnit_Framework_TestCase ;

class ValidatorTest extends PHPUnit_Framework_TestCase{

    public function setUp() : void {
        parent::setUp();
    }

    public function testValidateMinLength(){
        $validator = new Validator([
          'a' => ['min' => 3 ] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 123]),(string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => 0]));
    }

    public function testValidateMaxLength(){
        $validator = new Validator([
          'a' => ['max' => 15 ] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 12 ]));

        $this->assertFalse($validator->validate(['a' => 1234]));
    }

    public function testValidateMinCharsLength(){
        $validator = new Validator([
          'a' => ['minlength' => 3 ] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'abcde']),(string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => 'a']));
    }

    public function testValidateMaxCharsLength(){
        $validator = new Validator([
          'a' => ['maxlength' => 7 ] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'fin' ]));

        $this->assertFalse($validator->validate(['a' => 'loremipsum']));
    }

    public function testValidateRangeNumeric(){
        $validator = new Validator([
          'a' => ['range' => '12.5,39.6' ] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 20 ]));

        $this->assertFalse($validator->validate(['a' => 12-4 ]));
        $this->assertFalse($validator->validate(['a' => 39.7]));
    }

    public function testValidateRangeArray(){
        $validator = new Validator([
          'a' => ['range' => '5,8' ] ,
        ]);

        $this->assertTrue($validator->validate(['a' => range(1,5) ]));

        $this->assertFalse($validator->validate(['a' => [] ]));
        $this->assertFalse($validator->validate(['a' => range(1,10)]));
    }

    public function testValidateRangeNumericArrayDefined(){
        $validator = new Validator([
          'a' => ['range' => [12.5, 39.6] ] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 20 ]));

        $this->assertFalse($validator->validate(['a' => 12-4 ]));
        $this->assertFalse($validator->validate(['a' => 39.7]));
    }

    public function testValidateDefined(){
        $validator = new Validator([
          'a' => 'defined' ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'cosa']));

        $this->assertFalse($validator->validate(['b' => NAN]));
    }

    public function testValidateRegex(){
        $validator = new Validator([
          'a' => ['regex' => '/^\w{3}$/'] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'Wtf']),'error:' .(string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => 'm']));
        $this->assertFalse($validator->validate(['a' => 'mnwa']));
    }

    public function testValidateIsInt(){
        $validator = new Validator([
          'a' => 'is_int' ,
        ]);

        $this->assertTrue($validator->validate(['a' => 33]));

        $this->assertFalse($validator->validate(['a' => 'bla']));
    }

    public function testValidateIsArray(){
        $validator = new Validator([
          'a' => 'is_array' ,
        ]);

        $this->assertTrue($validator->validate(['a' => []]));

        $this->assertFalse($validator->validate(['a' => 'bla']));
    }

    public function testValidateIsObject(){
        $validator = new Validator([
          'a' => 'is_object' ,
        ]);

        $this->assertTrue($validator->validate(['a' => new stdClass()]));

        $this->assertFalse($validator->validate(['a' => 'bla']));
    }

    public function testValidateIsScalar(){
        $validator = new Validator([
          'a' => 'is_scalar' ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'a 3 2.5']));

        $this->assertFalse($validator->validate(['a' => []]));
    }

    public function testValidateChainRulesBasic(){
        $validator = new Validator([
          'a' => 'regex:/^a.*z$/' ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'a 3 2.5z']),(string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => null]),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => []]));
        $this->assertFalse($validator->validate(['a' => new stdClass()]),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => '3']),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => '3.1415928']),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => 'as32123']),(string)$validator->getLastError());
    }

    public function testValidateChainRules(){
        $validator = new Validator([
          'a' => 'defined|is_scalar|min:2|maxlength:6|regex:/^a.*z$/' ,
        ]);
        $this->assertTrue($validator->validate(['a' => 'a 3.5z']),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => null]),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => []]));
        $this->assertFalse($validator->validate(['a' => new stdClass()]),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => '3']),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => '3.1415928']),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => 'as32123']),(string)$validator->getLastError());
    }

    public function testValidateChainRulesComplex(){
        $validator = new Validator([
          'a' => 'defined|is_scalar|minlength:2|maxlength:6|regex:/^a.*z$/' ,
          'b' => ['is_array' , 'min' => 3] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'a 3.5z','b' => [1,2,'z','alfa']]),(string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => null,'b' => [1,2,'z']]),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => [],'b' => [1,2,'z']]));
        $this->assertFalse($validator->validate(['a' => new stdClass(),'b' => [1,2,'z']]),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => '3','b' => [1,2,'z']]),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => '3.1415928','b' => [1,2,'z']]),(string)$validator->getLastError());
        $this->assertFalse($validator->validate(['a' => 'as32123','b' => [1,2,'z']]),(string)$validator->getLastError());

        // b field
        $this->assertTrue($validator->validate([
          'a' => 'a 3.5z',
          'b' => ['t','q' , 'm']]),
                (string)$validator->getLastError());

        $this->assertFalse($validator->validate([
          'a' => 'a 3.5z' ,
          'b' => ['2'] ,
          ]),(string)$validator->getLastError());
    }

    public function testValidateRarePHPFunction(){
        $validator = new Validator([
          'a' => ['range'  ] ,
        ]);

        $this->assertFalse($validator->validate(['a' => 123]),(string)$validator->getLastError());
    }

    public function testValidateClosureBasic(){

        $validator = new Validator([
          'a' => [function ($field, $value,&$msg) {
                    if($value == 'bla'){
                        return true ;
                    }
                    return false ;
                  }] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'bla']),(string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => 'tos']),(string)$validator->getLastError());

        $this->assertEquals("The field 'a' does not pass closure validator",(string)$validator->getLastError());
    }

    public function testValidateClosure(){
        $msg1 = 'The field is not bla' ;
        $validator = new Validator([
          'a' => [function ($input, $value,&$msg) use ($msg1){
                    if($value == 'bla'){
                        return true ;
                    }
                    $msg = $msg1 ;
                    return false ;
                  }] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'bla']),(string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => 'tos']),(string)$validator->getLastError());

        $this->assertEquals($msg1,(string)$validator->getLastError());
    }

    public function testValidateClosureUsingInputParameter(){
        $msg1 = 'The field is not bla or field b is not valid' ;
        $validator = new Validator([
          'a' => [function ($input, $value,&$msg) use ($msg1){
                    //validating other field this validator
                    if($value == 'bla' && isset($input['b']) && $input['b'] == 'fin'){
                        return true ;
                    }

                    $msg = $msg1 ;
                    return false ;
                  }] ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'bla','b' => 'fin']), (string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => 'bla']),(string)$validator->getLastError());

        $this->assertEquals($msg1,(string)$validator->getLastError());
    }



    public function testValidateClosureDirectly(){
        $msg1 = 'The field is not bla' ;
        $validator = new Validator([
          'a' => function ($field, $value,&$msg) use ($msg1){
                    if($value == 'bla'){
                        return true ;
                    }
                    $msg = $msg1 ;
                    return false ;
                  } ,
        ]);

        $this->assertTrue($validator->validate(['a' => 'bla']),(string)$validator->getLastError());

        $this->assertFalse($validator->validate(['a' => 'tos']),(string)$validator->getLastError());

        $this->assertEquals($msg1,(string)$validator->getLastError());
    }

    public function testValidateGetAllErrors(){
      $validator = new Validator([
        'a' => 'defined|is_scalar|minlength:2|maxlength:6|regex:/^a.*z$/' ,
        'b' => ['is_array' , 'min' => 3] ,
      ]);
      $this->assertFalse($validator->validate(['a' => (array)'a 3.5z','b' => ['z','alfa']], false) ,
      'must have validation errors') ;

      $this->assertCount(2, $validator->getErrors());
    }

    /*public function testValidateNoStopError(){
      $validator = new Validator([
        'a' => 'nostop|defined|minlength:5|maxlength:3|regex:/^ax$/' ,
        'b' => ['is_array' , 'min' => 3] ,
      ]);
      $this->assertFalse($validator->validate(['a' => 'a 3.5','b' => ['z','alfa']]) ,
      'must have validation errors') ;
      print_r($validator->getErrors());
      $this->assertCount(2, $validator->getErrors());
  }  */
}
