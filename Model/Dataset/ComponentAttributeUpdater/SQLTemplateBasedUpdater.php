<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset\ComponentAttributeUpdater;

use JMS\DiExtraBundle\Annotation as DI;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;
use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service()
 * @DI\Tag("postgres_helper.component_attribute_updater")
 */
class SQLTemplateBasedUpdater extends AbstractComponentAttributeUpdater
{
    public function listAttributesThatCanUpdate(Dataset $dataset, $componentName, array $attributeNames)
    {
        $sqlTemplateManager = $dataset->getDatasetManager()->getSQLTemplatManager();
        $schema = $dataset->getSchema();
        $type = $dataset->getProperty('type');
        //var_dump($dataset, $componentName);
        
        $result = [];
        foreach ($attributeNames as $attributeName) {
            $templates = [
                "$schema#$componentName/attributes/$attributeName.$type",
                "$schema#$componentName/attributes/$attributeName",
            ];
            if ($sqlTemplateManager->existSomeOf($templates)) {
                array_push($result, $attributeName);
            }
        }
        return $result;
    }
    
    public function update(Dataset $dataset, $componentName, array $attributeNames, array $recordIds = null)
    {
        $sqlTemplateManager = $dataset->getDatasetManager()->getSQLTemplatManager();

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
            
            $sqlTemplateManager->run($templates, [
                    'schema'=>$dataset->getSchema(),
                    'datasetName'=>$dataset->getName(),
                    'componentName'=>$componentName,
                    'recordIdsAsStr'=>$recordIdsAsStr,
                    ]);
        }
    }

}
