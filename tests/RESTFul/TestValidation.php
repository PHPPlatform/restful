<?php

namespace PhpPlatform\Tests\RESTFul;

use PHPUnit\Framework\TestCase;
use PhpPlatform\RESTFul\Validation;

class TestValidation extends TestCase{
    
    /**
     * @dataProvider dataProviderValidation
     */
    function testValidation($data,$validationSequence,$expectedErrors){
        $validation = new Validation('data', $data);
        call_user_func($validationSequence,$validation);
        $this->assertEquals($expectedErrors, $validation->getValidationErrors());
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
            ]
                
        ];
    }
    
}