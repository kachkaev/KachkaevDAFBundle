<?php

namespace Kachkaev\PostgresHelperBundle\Tests\Model\Validator;

class NameValidatorTest extends AbstractValidatorTest
{
    protected $validValues = ['a', 'aa', 'abc123', 'ab_cde', 'one_two_3'];
    protected $invalidValues = [null, '', ' ', ' a', 'a ', 'test_', '42a', 'some__thing', 'Capitals', '_abc', '_'];
    protected $class = '\Kachkaev\PostgresHelperBundle\Model\Validator\NameValidator';
}