<?php
namespace Kachkaev\DatasetAbstractionBundle\Model\Validator;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * Checks schema name validity

 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("dataset_abstraction.validator.schema_name")
 */

class SchemaNameValidator implements ValidatorInterface
{
    private $pattern = "/^[a-z]([a-z0-9]*(_([a-z0-9]+))?)*$/";
    
    public function isValid($value)
    {
        return (bool) preg_match($this->pattern, $value);
    }

    public function assertValid($value)
    {
        if (!$this->isValid($value)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid name for a schema. It must be alphanumeric, lowercase and have not more than one underscore in a row (see guidelines for details).', var_export($value, true)));
        }
    }
}
