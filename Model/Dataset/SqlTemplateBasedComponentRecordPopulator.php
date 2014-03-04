<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation as DI;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;
use Kachkaev\PostgresHelperBundle\Model\SQLTemplateManager;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("ph.dataset_component_record_populator.sql_template_based")
 */
class SqlTemplateBasedComponentRecordPopulator extends AbstractComponentRecordPopulator
{
    protected $schema = null;
    protected $types = null;
    
    public function getSearchableTemplateNames(Dataset $dataset, $componentName)
    {
        $datasetType = $dataset->getProperty('type');
        $datasetSchemaName = $dataset->getSchema();
        
        $parsedComponentName = $dataset->getComponentManager()->parse($componentName);
        if ($parsedComponentName['familyName'] !== null) {
            $componentDirectory = $parsedComponentName['familyName'].'__';
        } else {
            $componentDirectory = $componentName;
        }
        $templateNames = [];
        if ($datasetType) {
            $templateNames []= sprintf('%s#%s/populate.%s', $datasetSchemaName, $componentDirectory, $datasetType);
        }
        $templateNames []= sprintf('%s#%s/populate', $datasetSchemaName, $componentDirectory);
            
        return $templateNames;
    }
    
    public function hasTemplateToExecute(Dataset $dataset, $componentName)
    {
        return $this->getTemplateNameToExecute($dataset, $componentName) != null;
    } 

    public function getTemplateNameToExecute(Dataset $dataset, $componentName)
    {
        $templateNames = $this->getSearchableTemplateNames($dataset, $componentName);
        foreach ($templateNames as $templateName) {
            if ($this->sqlTemplateManager->exists($templateName)) {
                return $templateName;
            }
        }
        
        return null;
    }
    
    protected function doPopulate(Dataset $dataset, $componentName, array $options, OutputInterface $output)
    {
        $templateNameToExecute = $this->getTemplateNameToExecute($dataset, $componentName);
        
        if (!$templateNameToExecute) {
            throw new \LogicException('Cannot run sql-tempalte-based populator as corresponding templates are not found. Were looking for %s', implode(', ', $this->getSearchableTemplateNames($dataset, $componentName)));
        }
        
        $parsedComponentName = $dataset->getComponentManager()->parse($componentName);
        
        // Cleaning the component
        $dataset->getComponentRecordManager()->clean($componentName);
        
        // Calling the template
        $this->sqlTemplateManager->run([$templateNameToExecute],
            [
                'schema'=>$dataset->getSchema(),
                'datasetName'=>$dataset->getName(),
                'componentInstanceName'=>$parsedComponentName['instanceName']
            ]);
    }
}