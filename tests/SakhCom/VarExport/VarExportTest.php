<?php

namespace Tests\SakhCom\VarExport;

use PHPUnit\Framework\TestCase;
use SakhCom\VarExport\VarExport;
use Tests\SakhCom\VarExport\Stub\AnotherStubObject;
use Tests\SakhCom\VarExport\Stub\StubObject;

class VarExportTest extends TestCase
{

    /**
     * @dataProvider simpleDataProvider
     * @param $variable
     */
    public function testSimple($variable)
    {
        $this->assertEquals(
            var_export($variable, true),
            (new VarExport($variable))->export()
        );

        $this->assertEquals(
            (string)var_export($variable, true),
            (string)new VarExport($variable)
        );
    }

    public function simpleDataProvider()
    {
        return [
            'simple string'             => ['test string'],
            'string with special chars' => ['"\"\'\some' . "\n\\strin'\"g"],
            'true'                      => [true],
            'false'                     => [false],
            'integer'                   => [100],
            'float'                     => [2.154],
            'resource'                  => [STDOUT],
            'array'                     => [
                [1, 2, 3]
            ],
            'multiple array'            => [
                [
                    [1, 2, 3],
                    [4, 5, 6]
                ],
            ],
            'nested array'              => [
                1,
                [
                    2,
                    3
                ]
            ],
            'hash table'                => [
                'key'         => 'value',
                'another key' => [
                    'nested key' => false
                ],
            ],
            'array with mixed keys'     => [
                0   => 123,
                '2' => 456
            ],
            'generator'                 => [
                [
                    (function () {
                        yield 1;
                    })()
                ]
            ],
            'closure'                   => [
                [
                    function () {
                        return 1;
                    }
                ]
            ],
            'simple object'             => [
                (object)['key' => 'value']
            ],
            'object with array'         => [
                (object)[
                    'key' => [
                        1,
                        2,
                        3
                    ]
                ]
            ]
        ];
    }

    public function testSimpleClassObject()
    {
        $value = new StubObject();
        $expected = <<<EXPECTED
Tests\SakhCom\VarExport\Stub\StubObject::__set_state(array(
   'a' => 1,
   'b' => 2,
   'c' => 3,
))
EXPECTED;

        $this->assertEquals($expected, (new VarExport($value))->export());
    }

    public function testChildrenClassObject()
    {
        $value = new AnotherStubObject();
        $expected = <<<EXPECTED
Tests\SakhCom\VarExport\Stub\AnotherStubObject::__set_state(array(
   'a' => 1,
   'c' => 4,
   'b' => 2,
))
EXPECTED;
        $this->assertEquals($expected, (new VarExport($value))->export());
    }

    public function testArrayOfObjects()
    {
        $value = [
            new StubObject(),
            new AnotherStubObject(),
        ];
        $expected = <<<EXPECTED
array (
  0 => 
  Tests\SakhCom\VarExport\Stub\StubObject::__set_state(array(
     'a' => 1,
     'b' => 2,
     'c' => 3,
  )),
  1 => 
  Tests\SakhCom\VarExport\Stub\AnotherStubObject::__set_state(array(
     'a' => 1,
     'c' => 4,
     'b' => 2,
  )),
)
EXPECTED;

        $this->assertEquals($expected, (new VarExport($value))->export());
    }

    public function testExportClosure()
    {
        $closure = function () {
            return 1;
        };

        $this->assertEquals(
            var_export($closure, true),
            (new VarExport($closure))->export()
        );
    }

    public function testDeepArray()
    {
        $value = [
            'a' => [
                'b' => [
                    'c' => 1
                ]
            ]
        ];
        $expected = <<<EXPECTED
array (
  'a' => 
  array (
    'b' => 
    array (...),
  ),
)
EXPECTED;

        $this->assertEquals($expected, (new VarExport($value))->export(2));
    }

    public function testDeepObject()
    {
        $value = (object)[];
        $value->ref = [
            'a' => [
                'b' => [
                    'c' => 1
                ]
            ]
        ];

        $expected = <<<EXPECTED
(object) array(
   'ref' => 
  array (
    'a' => 
    array (...),
  ),
)
EXPECTED;

        $this->assertEquals($expected, (new VarExport($value))->export(2));
    }

    public function testCircularReference()
    {
        $a = (object)[];
        $b = (object)[];
        $a->b = $b;
        $b->a = $a;

        $expected = <<<EXPECTED
(object) array(
   'b' => 
  (object) array(
     'a' => 
    (object) array(...),
  ),
)
EXPECTED;

        $this->assertEquals($expected, (new VarExport($a))->export(2));
    }
}
