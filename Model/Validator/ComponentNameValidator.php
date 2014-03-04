<?php
namespace Kachkaev\DatasetAbstractionBundle\Model\Validator;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * Checks dataset component name validity

 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("dataset_abstraction.validator.component_name")
 */

class ComponentNameValidator implements ValidatorInterface
{
    // XXX pattern now both matches names for components and views; consider revising if they get separated
    private $pattern = "/^_?[a-z]([a-z0-9]*(__?([a-z0-9]+))?)*$/";
    
    public function isValid($value)
    {
        return (bool) preg_match($this->pattern, $value) && $value !== 'meta';
    }

    public function assertValid($value)
    {
        if (!$this->isValid($value)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid name for a component. It must be alphanumeric, lowercase, have not more than two underscores in a row, not start or end with underscore and be not equal to "meta".', var_export($value, true)));
        }
    }
}