<?php

namespace Kachkaev\DatasetAbstractionBundle\Tests\Model\Validator;

abstract class AbstractValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $validValues;
    protected $invalidValues;
    protected $class;

    protected function getValidator()
    {
         return new $this->class;
    }
    

    public function testIsValid()
    {
        $validator = $this->getValidator();
        
        foreach ($this->validValues as $value) {
            $this->assertTrue($validator->isValid($value), $value);
        }
        
        foreach ($this->invalidValues as $value) {
            $this->assertFalse($validator->isValid($value), $value);
        }
    }

    public function testAssertValid()
    {
        $validator = $this->getValidator();
    
        foreach ($this->validValues as $value) {
            try {
                $validator->assertValid($value);
            } catch (\InvalidArgumentException $e) {
                $this->fail(sprintf('InvalidArgumentException was thrown for %s, but was not supposed to', $value));
            }
        }
    
        foreach ($this->invalidValues as $value) {
            try {
                $validator->assertValid($value);
                $this->fail(sprintf('InvalidArgumentException was not thrown for %s, but was supposed to', $value));
            } catch (\InvalidArgumentException $e) {
            }
        }
    }
}

