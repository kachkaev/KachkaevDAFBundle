<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset\ComponentAttributeUpdater;
use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;

abstract class AbstractComponentAttributeUpdater
{
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

     * @param Dataset $dataset
     * @param string $componentName
     * @param array $attributeNames
     * @param array $recordIds
     * 
     * @return undefined
     */
    abstract public function update(Dataset $dataset, $componentName, array $attributeNames, array $recordIds = null);
}
