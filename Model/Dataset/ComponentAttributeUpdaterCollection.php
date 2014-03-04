<?php
namespace Kachkaev\DatasetAbstractionBundle\Model\Dataset;

use Kachkaev\DatasetAbstractionBundle\Model\Dataset\ComponentAttributeUpdater\AbstractComponentAttributeUpdater;
use Kachkaev\DatasetAbstractionBundle\Model\Dataset\ComponentAttributeUpdater\ComponentAttributeUpdaterInterface;

use Kachkaev\DatasetAbstractionBundle\Model\Dataset\Dataset;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Simply keeps all services tagged as dataset_abstraction.component_attribute_updater
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("dataset_abstraction.component_attribute_updaters")
 */
class ComponentAttributeUpdaterCollection
{
    protected $componentAttributeUpdaters = [];
    
    public function add(AbstractComponentAttributeUpdater $componentAttributeUpdater)
    {
        array_push($this->componentAttributeUpdaters, $componentAttributeUpdater);
    }
    
    public function getAll()
    {
        return $this->componentAttributeUpdaters;
    }
}
