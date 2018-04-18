<?php

namespace PhpPlatform\Tests\RESTFul;

use PHPUnit\Framework\TestCase;
use PhpPlatform\RESTFul\Validation;

class TestValidation extends TestCase{
    
    /**
     * @dataProvider dataProviderValidation
     */
    function testValidation($data,$validationSequence,$expectedErrors,$expectedData = null){
        $originalData = json_decode(json_encode($data),true);
        $validation = new Validation('data', $data);
        call_user_func($validationSequence,$validation);
        $this->assertEquals($expectedErrors, $validation->getValidationErrors());
        if($expectedData != null){
            $this->assertEquals($expectedData, $data);
        }else{
            $this->assertEquals($originalData, $data);
        }
    }
    
    function dataProviderValidation(){
        return [
            'without any validation sequence'=>[
                ['a','b','c'],
                function($validation){
                    
                },
                []
            ],
            'with simple validation sequence'=>[
                ['a','b','c'],
                function($validation){
                    $validation->isString();
                },
                'invalid'
            ],
            'containsOnly success'=>[
                ['a'=>'x','b'=>'y','c'=>'z'],
                function($validation){
                    $validation->containsOnly(['a','b','c','d']);
                },
                []
            ],
            'containsOnly failure'=>[
                ['a'=>'x','b'=>'y','c'=>'z'],
                function($validation){
                    $validation->containsOnly(['b','c','d']);
                },
                'invalid'
            ],
            'containsAll success'=>[
                ['a'=>'x','b'=>'y','c'=>'z'],
                function($validation){
                    $validation->containsAll(['a','b']);
                },
                []
            ],
            'containsAll failure'=>[
                ['a'=>'x','b'=>'y','c'=>'z'],
                function($validation){
                    $validation->containsAll(['b','c','d']);
                },
                'invalid'
            ],
            'containsExactly success'=>[
                ['a'=>'x','b'=>'y','c'=>'z'],
                function($validation){
                    $validation->containsExactly(['a','b','c']);
                },
                []
            ],
            'containsExactly failure'=>[
                ['a'=>'x','b'=>'y','c'=>'z'],
                function($validation){
                    $validation->containsExactly(['b','c','d']);
                },
                'invalid'
            ],
            'containsExactly failure 2'=>[
                ['a'=>'x','b'=>'y','c'=>'z'],
                function($validation){
                    $validation->containsExactly(['a','b','c','d']);
                },
                'invalid'
            ],
            'sub validation success'=>[
                ['a'=>'x','b'=>['A'=>200],'c'=>'z'],
                function($validation){
                    $validation->containsOnly(['a','b','c'])->key('b')->required()->key('A')->isNumeric()->inRange(100,200);
                },
                []
            ],
            'sub validation failure'=>[
                ['a'=>'x','b'=>['A'=>200],'c'=>'z'],
                function($validation){
                    $validation->containsOnly(['a','b','c'])->key('b')->required()->key('A')->isNumeric()->in([10,20,30,40]);
                },
                ['b'=>['A'=>'invalid']]
            ],
            'require failure'=>[
                ['a'=>'x','b'=>['A'=>200],'c'=>'z'],
                function($validation){
                    $validation->containsOnly(['a','b','c','d'])->key('d')->required();
                },
                ['d'=>'missing'],
                ['a'=>'x','b'=>['A'=>200],'c'=>'z','d'=>null]
            ],
            'default value'=>[
                ['a'=>'x','b'=>['A'=>200],'c'=>'z'],
                function($validation){
                    $validation->containsOnly(['a','b','c','d'])->key('d')->defaultValue('P');
                },
                [],
                ['a'=>'x','b'=>['A'=>200],'c'=>'z','d'=>'P']
            ],
            'isNumeric Failure'=>[
                ['a'=>'x','b'=>['A'=>200],'c'=>'z'],
                function($validation){
                    $validation->key('c')->isNumeric()->inRange(0);
                },
                ['c'=>'invalid']
            ],
            'isInt Success'=>[
                ['a'=>'x','b'=>['A'=>200],'c'=>'z'],
                function($validation){
                    $validation->key('b')->key('A')->isInt();
                },
                []
            ],
            'isInt Failure'=>[
                ['a'=>'x','b'=>['A'=>200.50],'c'=>'z'],
                function($validation){
                    $validation->key('b')->key('A')->isInt();
                },
                ['b'=>['A'=>'invalid']]
            ],
            'inRange Failure 1'=>[
                ['a'=>'x','b'=>['A'=>200],'c'=>'z'],
                function($validation){
                    $validation->key('b')->key('A')->isInt()->inRange(400);
                },
                ['b'=>['A'=>'invalid']]
            ],
            'inRange Failure 2'=>[
                ['a'=>'x','b'=>['A'=>200],'c'=>'z'],
                function($validation){
                    $validation->key('b')->key('A')->isInt()->inRange(0,100);
                },
                ['b'=>['A'=>'invalid']]
            ],
            'isTimestamp Success'=>[
                ['a'=>'x','b'=>'15th April 2018','c'=>'z'],
                function($validation){
                    $validation->key('b')->isTimestamp();
                },
                []
            ],
            'isTimestamp Failure'=>[
                ['a'=>'x','b'=>'2018-April-2802','c'=>'z'],
                function($validation){
                    $validation->key('b')->isTimestamp();
                },
                ['b'=>'invalid']
            ],
            'isArray Success'=>[
                ['a'=>[['p'=>1],['q'=>2]],'b'=>'y','c'=>'z'],
                function($validation){
                    $validation->key('a')->isArray()->hasCount(1,3);
                },
                []
            ],
            'isArray Failure'=>[
                ['a'=>'{"p":1,"q":2}','b'=>'y','c'=>'z'],
                function($validation){
                    $validation->key('a')->isArray()->hasCount(1,3);
                },
                ['a'=>'invalid']
            ],
            'hasCount Failure'=>[
                ['a'=>[['p'=>1],['q'=>2]],'b'=>'y','c'=>'z'],
                function($validation){
                    $validation->key('a')->isArray()->hasCount(3,10);
                },
                ['a'=>'invalid']
            ],
            'isBoolean Success'=>[
                ['a'=>'x','b'=>true,'c'=>'z'],
                function($validation){
                    $validation->key('b')->isBoolean();
                },
                []
            ],
            'isBoolean Failure'=>[
                ['a'=>'x','b'=>200,'c'=>'z'],
                function($validation){
                    $validation->key('b')->isBoolean();
                },
                ['b'=>'invalid']
            ],
            'match Success'=>[
                ['a'=>'Raghavendra@gmail.com','b'=>true,'c'=>'z'],
                function($validation){
                    $validation->key('a')->match('/([a-z]|[A-Z]|\.)*\@([a-z]|[A-Z]|\.)*/');
                },
                []
            ],
            'match Failure'=>[
                ['a'=>'Raghavendragmail.com','b'=>true,'c'=>'z'],
                function($validation){
                    $validation->key('a')->match('/([a-z]|[A-Z]|\.)*\@([a-z]|[A-Z]|\.)*/');
                },
                ['a'=>'invalid']
            ],
            'hasLength Success'=>[
                ['a'=>'Raghavendra@gmail.com','b'=>true,'c'=>'z'],
                function($validation){
                    $validation->key('a')->match('/([a-z]|[A-Z]|\.)*\@([a-z]|[A-Z]|\.)*/')->hasLength(1,255);
                },
                []
            ],
            'hasLength Failure'=>[
                ['a'=>'Raghavendragmail.com','b'=>true,'c'=>'z'],
                function($validation){
                    $validation->key('a')->hasLength(1,10);
                },
                ['a'=>'invalid']
            ],
            'ifPresent if present and valid'=>[
                ['a'=>'Raghavendragmail.com','b'=>true,'c'=>'z'],
                function($validation){
                    $validation->key('a')->ifPresent()->isString()->hasLength(1,100);
                },
                []
            ],
            'ifPresent if present and invalid'=>[
                ['a'=>'Raghavendragmail.com','b'=>true,'c'=>'z'],
                function($validation){
                    $validation->key('a')->ifPresent()->isString()->hasLength(1,10);
                },
                ['a'=>'invalid']
            ],
            'ifPresent if not present'=>[
                ['b'=>true,'c'=>'z'],
                function($validation){
                    $validation->key('a')->ifPresent()->isString()->hasLength(1,100);
                },
                []
            ],
        ];
    }
    
}