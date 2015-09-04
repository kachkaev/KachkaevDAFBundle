<?php
namespace Kachkaev\DAFBundle\Model\Dataset;

use Kachkaev\DAFBundle\Model\Dataset\ComponentAttributeUpdater\AbstractComponentAttributeUpdater;
use Kachkaev\DAFBundle\Model\Dataset\Dataset;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 * Keeps all services tagged as daf.component_attribute_updater
 *
 * @DI\Service("daf.component_attribute_updaters")
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
