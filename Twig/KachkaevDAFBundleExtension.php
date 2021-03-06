<?php
namespace Kachkaev\DAFBundle\Twig;

use Doctrine\DBAL\Portability\Connection;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Extension;

/**
 *) @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("daf.twig_extension")
 * @DI\Tag("twig.extension")
 */
class KachkaevDAFBundleExtension extends Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    protected $container;

    protected $globalScopeVars = [];

    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        $functions = array();

        $mappings = array(
                'setGlobalScopeVar' => 'setGlobalScopeVar',
                'getGlobalScopeVar' => 'getGlobalScopeVar',
            );

        foreach ($mappings as $twigFunction => $method) {
            $functions[$twigFunction] = new \Twig_SimpleFunction($twigFunction, array($this, $method));
        }

        $safeMappings = array();

        foreach ($safeMappings as $twigFunction => $method) {
            $functions[$twigFunction] = new \Twig_SimpleFunction($twigFunction, array($this, $method), array('is_safe' => array('html')));
        }

        return $functions;
    }

     public function getFilters() {
        return array(
            'repeat'   => new \Twig_SimpleFilter('repeat', 'str_repeat'),
            'to_array'   => new \Twig_SimpleFilter('to_array', array($this, 'toArray')),
        );
    }

    public function getGlobals()
    {
        return [
            'mainDBOwner' => $this->container->get('doctrine.dbal.main_connection')->getParams()['user'],
        ];
    }

    public function getName()
    {
        return 'KachkaevDAFBundleExtension';
    }

    public function setGlobalScopeVar($key, $value)
    {
        $this->globalScopeVars[$key] = $value;
    }

    public function getGlobalScopeVar($key)
    {
        return $this->globalScopeVars[$key];
    }

    public function toArray($arrayElement, $count)
    {
        return array_fill(0, $count, $arrayElement);
    }
}
