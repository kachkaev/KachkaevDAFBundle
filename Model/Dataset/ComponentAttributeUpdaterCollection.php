<?php
namespace Kachkaev\PostgresHelperBundle\Model\Dataset;

use Kachkaev\PostgresHelperBundle\Model\Dataset\ComponentAttributeUpdater\AbstractComponentAttributeUpdater;
use Kachkaev\PostgresHelperBundle\Model\Dataset\ComponentAttributeUpdater\ComponentAttributeUpdaterInterface;

use Kachkaev\PostgresHelperBundle\Model\Dataset\Dataset;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Simply keeps all services tagged as postgres_helper.component_attribute_updater
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("postgres_helper.component_attribute_updaters")
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
