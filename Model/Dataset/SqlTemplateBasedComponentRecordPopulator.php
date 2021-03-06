<?php
namespace Kachkaev\DAFBundle\Model\Dataset;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation as DI;

use Kachkaev\DAFBundle\Model\Dataset\Dataset;
use Kachkaev\DAFBundle\Model\TemplateManaging\SQLTemplateManager;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("daf.dataset_component_record_populator.sql_template_based")
 */
class SqlTemplateBasedComponentRecordPopulator extends AbstractComponentRecordPopulator
{
    protected $domainName = null;
    protected $types = null;

    public function getSearchableTemplateNames(Dataset $dataset, $componentName)
    {
        $datasetType = $dataset->getProperty('type');
        $domainName = $dataset->getDomainName();

        $parsedComponentName = $dataset->getComponentManager()->parse($componentName);
        if ($parsedComponentName['familyName'] !== null) {
            $componentDirectory = $parsedComponentName['familyName'].'__';
        } else {
            $componentDirectory = $componentName;
        }
        $templateNames = [];
        if ($datasetType) {
            $templateNames []= sprintf('%s#components/%s/populate.%s', $domainName, $componentDirectory, $datasetType);
        }
        $templateNames []= sprintf('%s#components/%s/populate', $domainName, $componentDirectory);

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


        // Desiding what variables to include
        $templateVars = [
                'domainName'=>$dataset->getDomainName(),
                'datasetName'=>$dataset->getName(),
                'componentInstanceName'=>$parsedComponentName['instanceName'],
                'componentProperties' => $dataset->listComponentProperties($componentName)
            ];

        // Calling the template
        $this->sqlTemplateManager->run([$templateNameToExecute], $templateVars);
    }
}