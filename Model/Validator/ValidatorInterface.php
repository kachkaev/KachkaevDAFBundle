<?php
namespace Kachkaev\DAFBundle\Model\Validator;

interface ValidatorInterface
{
    public function isValid($value);
    public function assertValid($value);
}
