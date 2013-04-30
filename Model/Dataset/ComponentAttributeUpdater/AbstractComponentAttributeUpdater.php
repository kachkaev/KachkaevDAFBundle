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
    
    /**
     * Saves all data to the db
     * @param SQLTemplateManager $sqlTemplateManager
     * @param array $attributeNames [name1, name2, ...]
     * @param array $data [id1 => [attr1, attr2, ...], id2 => ..., ....]
     */
    public function saveValuesToDB(SQLTemplateManager $sqlTemplateManager, array $attributeNames, array $data)
    {
        var_dump('~~~~~~HERE~~~~~~~');
        die();
    }
}
