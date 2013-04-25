<?php
namespace Kachkaev\PostgresHelperBundle\Twig;

use Doctrine\DBAL\Portability\Connection;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Extension;
use Twig_Filter_Method;
use Twig_Function_Method;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("pr.photosets.twig_extension")
 * @DI\Tag("twig.extension")
 */
class KachkaevPostgresHelperBundleExtension extends Twig_Extension
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
            $functions[$twigFunction] = new Twig_Function_Method($this, $method);
        }

        $safeMappings = array();

        foreach ($safeMappings as $twigFunction => $method) {
            $functions[$twigFunction] = new Twig_Function_Method($this, $method, array('is_safe' => array('html')));
        }

        return $functions;
    }
    
    public function getGlobals()
    {
        return [
            'mainDBOwner' => $this->container->get('doctrine.dbal.main_connection')->getParams()['user'],
        ];
    }

    public function getName()
    {
        return 'KachkaevPRKernelExtension';
    }
    
    public function setGlobalScopeVar($key, $value)
    {
        $this->globalScopeVars[$key] = $value;
    }

    public function getGlobalScopeVar($key)
    {
        return $this->globalScopeVars[$key];
    }
}