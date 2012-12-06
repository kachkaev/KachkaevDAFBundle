<?php
namespace Kachkaev\PostgresHelperBundle\Model\Validator;

interface ValidatorInterface
{
    public function isValid($value);
    public function assertValid($value);
}
