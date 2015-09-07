<?php
namespace Kachkaev\DAFBundle\Model\Dataset\ComponentAttributeUpdater;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Model\TemplateManaging\SQLTemplateManager;

use Kachkaev\DAFBundle\Model\Dataset\Dataset;

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
     * Returns names of source component attributes that are needed by this updater
     * E.g. if attrA = attrB + attrC, and this attributeUpdater works with attrA,
     * then the method returns [attrB, attrC]
     *
     * @param Dataset $dataset
     * @param string $componentName
     * @param array $attributeNames
     */
    public function listSourceAttributes(Dataset $dataset, $componentName, array $attributeNames)
    {
        return [];
    }

    /**
     * Updates attributes in all/selected data records according to the built-in rules
     * Does not write to the DB as such, but results changes in $data, that are then written into the DB (externally)
     *
     * @param Dataset $dataset
     * @param string $componentName
     * @param array $attributeNames
     * @param array $data data to transform. Has the following format:
     *     id1 => [attr1=>value1, attr2=>value2]
     *     id2 => [attr1=>value1, attr2=>value2]
     * @param OutputInterface $output (optional)
     */
    public function update(Dataset $dataset, $componentName, array $attributeNames, array &$data, OutputInterface $output = null)
    {
        $this->validateAttributes($dataset, $componentName, $attributeNames);

        ini_set('memory_limit', '-1');
        $this->doUpdate($dataset, $componentName, $attributeNames, $data, $output);
    }

    /**
     * Internal methods that updates records, wrapped by update()
     *
     * @param Dataset $dataset
     * @param string $componentName
     * @param array $attributeNames
     * @param array $data
     * @param OutputInterface $output
     */
    protected abstract function doUpdate(Dataset $dataset, $componentName, array $attributeNames, array &$data, OutputInterface $output = null);

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
            throw new \InvalidArgumentException(sprintf('Component attribute updater cannot work with the given data: dataset - %s, component - , attributes - ', $dataset->getFullName(), $componentName, implode(' ', $attributeNames)));
        }
    }
}
