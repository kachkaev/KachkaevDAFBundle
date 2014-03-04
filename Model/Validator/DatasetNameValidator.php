<?php
namespace Kachkaev\DatasetAbstractionBundle\Model\Validator;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * Checks dataset name validity

 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("dataset_abstraction.validator.dataset_name")
 */

class DatasetNameValidator implements ValidatorInterface
{
    private $pattern = "/^[a-z]([a-z0-9]*(_([a-z0-9]+))?)*$/";
    
    public function isValid($value)
    {
        return (bool) preg_match($this->pattern, $value);
    }

    public function assertValid($value)
    {
        if (!$this->isValid($value)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid name for a dataset. It must be alphanumeric, lowercase and have not more than one underscore in a row (see guidelines for details).', var_export($value, true)));
        }
    }
}
