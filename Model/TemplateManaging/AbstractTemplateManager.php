<?php
namespace Kachkaev\DAFBundle\Model\TemplateManaging;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\EngineInterface;

use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Portability\Connection;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 */

class AbstractTemplateManager
{
    /**
     *  @var ContainerInterface */
    protected $container;

    /**
     *  @var EngineInterface */
    protected $templating;

    protected $templateNamesNamespaceLookup;

    protected $templateType = null; // Dir name inside Resources/views and template extension, e.g. Resources/views/mytype/a/b/c/d.mytype.twig

    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container"),
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->templating = $container->get('templating');
        $this->queryTemplatesNamespaceLookup = $container->getParameter('daf.query_templates_namespace_lookups');
    }

    /**
     * Checks whether the given template exists
     * @param string $templateName
     * @return boolean
     */
    public function exists($templateName)
    {
        $templateNamePath = $this->getTemplatePath($templateName);
        return $this->templating->exists($templateNamePath);
    }

    /**
     * Checks whether at least of given templates exist
     * @param array $templateNames
     * @return boolean
     */
    public function existSomeOf(array $templateNames)
    {
        foreach ($templateNames as $templateName) {
            $templateNamePath = $this->getTemplatePath($templateName);
            if ($this->templating->exists($templateNamePath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether all of given sql templates exists
     * @param array $templateNames
     * @return boolean
     */
    public function existAllOf(array $templateNames)
    {
        foreach ($templateNames as $templateName) {
            $templateNamePath = $this->getTemplatePath($templateName);
            if (!$this->templating->exists($templateNamePath)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Renders the first template found in the list of $templateNames
     * @param array|string $templateNames one or several templates to look for
     * @param array $templateParams
     * @return string
     * @throws \InvalidArgumentException if none of given queries are found
     */

    public function render($templateNames, $templateParams = [])
    {
        if (is_string($templateNames)) {
            $templateNames = [$templateNames];
        }
        if ($templateParams === null) {
            $templateParams = [];
        }

        foreach ($templateNames as $templateName) {
            $templatePath = $this->getTemplatePath($templateName);
            if ($this->templating->exists($templatePath)) {

                $strategy = $this->container->get('twig')->getExtension('escaper')->getDefaultStrategy($templatePath);
                $this->container->get('twig')->getExtension('escaper')->setDefaultStrategy(false);

                $result = $this->templating->render($templatePath, $templateParams);

                $this->container->get('twig')->getExtension('escaper')->setDefaultStrategy($strategy);

                return $result;
            }
        }

        if (sizeof($templateNames) == 1) {
            throw new \InvalidArgumentException(sprintf('Template %s was not found', $templateNames[0]));
        } else {
            throw new \InvalidArgumentException(sprintf('None of the following query templates were found: %s', implode(', ', $templateNames)));
        }

    }

    /**
     * Returns currently used template engine
     * @return \Symfony\Component\Templating\EngineInterface
     */
    public function getTemplating()
    {
        return $this->templating;
    }

    /**
     * Converts short address of sql template to twig-compatable format
     *
     * Example:
     *     dataset_abstraction#a/b/c → KachkaevDAFBundle:$templateType/a/b:c.$templateType.twig
     */
    protected function getTemplatePath($templateName)
    {
        if (!is_string($templateName) && !$templateName)
            throw new \InvalidArgumentException(sprintf('Template name must be a non-empty string, got %s', var_export($templateName, true)));

        $templateNameParts = explode("#", $templateName);
        if (array_key_exists(1, $templateNameParts))
        $templateNameParts[1] = $this->templateType.'/'.$templateNameParts[1];

        if ($templateNameParts[0] == 'dataset_abstraction') {
            $queryBundle = 'KachkaevDAFBundle';
        } else {
            if (!array_key_exists($templateNameParts[0], $this->queryTemplatesNamespaceLookup)) {
                throw new \InvalidArgumentException(sprintf('Don’t know where to search for templates starting with ‘%s’', $templateNameParts[0]));
            } else {
                $queryBundle = $this->queryTemplatesNamespaceLookup[$templateNameParts[0]]['bundle'];
            }
        }
        $result = sprintf('%s:%s.%s.twig',$queryBundle, $this->str_lreplace('/', ':', $templateNameParts[1]), $this->templateType);

        return $result;
    }

    public function getTemplateNamespacePath($domainName) {
        if (!array_key_exists($domainName, $this->queryTemplatesNamespaceLookup)) {
            throw new \InvalidArgumentException(sprintf('SQL template namespace %s not found, please add it to daf.query_templates_namespace_lookups parameter', $domainName));
        }
        return $this->queryTemplatesNamespaceLookup[$domainName]['path'];
    }

    // See http://stackoverflow.com/questions/3835636/php-replace-last-occurence-of-a-string-in-a-string
    private static function str_lreplace($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);

        if($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }
}
