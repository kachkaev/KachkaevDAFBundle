<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset\ComponentAttributeUpdater;
use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;

abstract class AbstractComponentAttributeUpdater
{
    protected $supportsNullForRecordIds = false;
    
    /**
     * Lists attributes that this attribute updater can update
     *
     * @param Dataset $dataset
     * @param string $componentName
     * @param array $attributeNames
     * 
     * @return array Array of attribute names that this attribute updater can update
     */
    abstract public function listAttributesThatCanUpdate(Dataset $dataset, $componentName, array $attributeNames);

    /**
     * Updates attributes in all/selected records according to the built-in rules
     * 
     * @param Dataset $dataset
     * @param string $componentName
     * @param array $attributeNames
     * @param array|null $recordIds can be null only if protected $supportsNullForRecordIds = true
     */
    public function update(Dataset $dataset, $componentName, array $attributeNames, array $recordIds = null)
    {
        $this->validateAttributes($dataset, $componentName, $attributeNames);

        if (is_array($recordIds)) {
            
        } else if (is_null($recordIds)) {
            if (!$this->supportsNullForRecordIds) {
                throw new \InvalidArgumentException('Record ids should be an array, null is not supported.');    
            }
        } else {
            throw new \InvalidArgumentException(sprintf('parameter $recordIds should be an array%s, given: %s', $this->supportsNullForRecordIds ? ' or null' : '', var_export($recordIds, true)));    
        }
        
        $this->doUpdate($dataset, $componentName, $attributeNames, $recordIds);
        
    }

    /**
     * Internal methods that updates records, wrapped by update()
     * 
     * @param Dataset $dataset
     * @param string $componentName
     * @param array $attributeNames
     * @param array|null $recordIds
     */
    protected abstract function doUpdate(Dataset $dataset, $componentName, array $attributeNames, array $recordIds = null);
    
    /**
     * Validates passed attributes and throws InvalidArgumentException
     * if the updater does not support any of the passed attribute names
     * 
     * Used in update()
     * 
     * @param Dataset $dataset
     * @param string $componentName
     * @param array $attributeNames
     * @throws InvalidArgumentException
     */
    protected function validateAttributes(Dataset $dataset, $componentName, array $attributeNames)
    {
        $list = $this->listAttributesThatCanUpdate($dataset, $componentName, $attributeNames);

        if (count($list) != count($attributeNames)) {
            throw new InvalidArgumentException(sprintf('Component attribute updater cannot work with the given data: dataset - %s, component - , attributes - ', $dataset->getFullName(), $componentName, implode(' ', $attributeNames)));
        }
    }
}
