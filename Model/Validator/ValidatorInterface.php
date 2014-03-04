<?php
namespace Kachkaev\DatasetAbstractionBundle\Model\Validator;

interface ValidatorInterface
{
    public function isValid($value);
    public function assertValid($value);
}
