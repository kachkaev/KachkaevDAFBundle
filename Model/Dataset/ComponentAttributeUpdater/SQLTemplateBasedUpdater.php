<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset\ComponentAttributeUpdater;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;
use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;

class SQLTemplateBasedUpdater implements ComponentAttributeUpdaterInterface
{
    /**
     * @var SQLTemplateManager
     */
    protected $sqlTemplateManager;
    
    public function __construct()
    {
        $this->sqlTemplateManager = $dataset->getDatasetManager()->getSQLTemplatManager();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Kachkaev\PostgresHelperBundle\Model\Dataset\ComponentAttributeUpdater\ComponentAttributeUpdaterInterface::listAttributesThatCanUpdate()
     */
    public function listAttributesThatCanUpdate(Dataset $dataset, $componentName, $attributeNames)
    {
        $result = [];
        foreach ($attributeNames as $attributeName) {
            $templates = [
                "$schema#$componentName/attributes/$attributeName.$type",
                "$schema#$componentName/attributes/$attributeName",
            ];
            if ($this->sqlTemplateManager->existsOneOf($templates)) {
                array_push($result, $attributeName);
            }
        }
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Kachkaev\PostgresHelperBundle\Model\Dataset\ComponentAttributeUpdater\ComponentAttributeUpdaterInterface::update()
     */
    public function update(Dataset $dataset, $componentName, array $attributeNames, array $recordIds = null)
    {
        $templateManager = $dataset->getDatasetManager()->getSQLTemplatManager();

        if ($recordIds !== null) {
            $recordIdsAsStr = implode(',', $recordIds);
        } else {
            $recordIdsAsStr = null;
        }
        
        foreach ($attributeNames as $attributeName) {
            $templates = [
            "$schema#$componentName/attributes/$attributeName.$type",
            "$schema#$componentName/attributes/$attributeName",
            ];
            
            $this->sqlTemplateManager->run($templates, [
                    'schema'=>$dataset->getSchema(),
                    'datasetName'=>$dataset->getName(),
                    'componentName'=>$componentName,
                    'recordIdsAsStr'=>$recordIdsAsStr,
                    ]);
        }
    }

}
