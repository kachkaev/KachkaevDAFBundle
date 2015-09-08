<?php
namespace Kachkaev\DAFBundle\Model\Dataset;
use Symfony\Component\Console\Output\OutputInterface;

use Kachkaev\DAFBundle\Model\TemplateManaging\SQLTemplateManager;

use Kachkaev\DAFBundle\Model\Dataset\Dataset;

abstract class AbstractPropertyUpdater
{
    protected $supportsNullForRecordIds = false;

    /**
     * Lists properties that this property updater can update
     *
     * @param Dataset $dataset
     * @param array $attributeNames
     *
     * @return array Array of property names that this property updater can update
     */
    abstract public function listPropertiesThatCanUpdate(Dataset $dataset, array $propertyNames);

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
    public function update(Dataset $dataset, array $propertyNames, OutputInterface $output = null)
    {
        $this->validateProperties($dataset, $attributeNames);

        $this->doUpdate($dataset, $attributeNames, $output);
    }

    /**
     * Internal methods that updates properties (wrapped by update)
     *
     * @param Dataset $dataset
     * @param array $propetyNames
     * @param OutputInterface $output
     */
    protected abstract function doUpdate(Dataset $dataset, array $propertyNames, OutputInterface $output = null);

    /**
     * Validates passed properties and throws InvalidArgumentException
     * if the updater does not support any of the passed property names
     *
     * Used in update()
     *
     * @param Dataset $dataset
     * @param array $propertyNames
     * @throws InvalidArgumentException
     */
    protected function validateProperties(Dataset $dataset, array $propertyNames)
    {
        $list = $this->listPropertiesThatCanUpdate($dataset, $componentName, $propertyNames);

        if (count($list) != count($propertyNames)) {
            throw new \InvalidArgumentException(sprintf('Property updater cannot work with the given data: dataset - %s, properties - %s', $dataset->getFullName(), implode(' ', $propertyNames)));
        }
    }
}
