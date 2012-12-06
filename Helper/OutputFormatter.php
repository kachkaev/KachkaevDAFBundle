<?php
namespace Kachkaev\PostgresHelperBundle\Helper;
use Symfony\Component\Console\Output\OutputInterface;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * Helps formatting output in CLI
 * 
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("pr.helper.output_formatter")
 */

class OutputFormatter
{
    protected $defaultListSpacing = 2;
    
    /**
     * 
     * @param OutputInterface $output
     * @param array $data An array to output (key=>value)
     * @param array $tags Styles to use during output
     */
    public function outputArrayAsAlignedList(OutputInterface $output, $data, $tags = ['info', ''])
    {
        $widths = [];
        
        $processedData = [];
        
        // Checking if array is associative
        $arrayIsAssoc = false;
        for ($i = sizeof($data) - 1; $i >= 0; --$i) {
            if (!array_key_exists($i, $data)) {
                $arrayIsAssoc = true;
                break;
            }
        }
        
        // Converting data to unified format
        foreach ($data as $k=>$v) {
            $currentProcessedData = $arrayIsAssoc ? [$k] : [];
            
            if (is_array($v)) {
                foreach ($v as $v2) {
                    $currentProcessedData []= $v2;
                }
            } else {
                $currentProcessedData []= $v;
            }
            $processedData []= $currentProcessedData;
        }
        
        // Calculating widths
        foreach($processedData as $currentProcessedData) {
            foreach ($currentProcessedData as $k => $v) {
                if (!array_key_exists($k, $widths) || strlen($v) > $widths[$k]) {
                    $widths[$k] = strlen($v);
                }
            }
        }

        // Generating output
        $result = [];
        foreach($processedData as $currentProcessedData) {
            $currentResult = '';
            foreach ($currentProcessedData as $k => $v) {
                if ($k != null)
                    $currentResult .= $v;
                if ($tags[$k]) {
                    $currentResult .= sprintf('<%s>%s</%s>', $tags[$k], $v, $tags[$k]); 
                }
                if ($k != sizeof($currentProcessedData) - 1)
                    $currentResult .= str_repeat(' ', $widths[$k] - strlen($v) + $this->defaultListSpacing);
            }
            $result []= $currentResult;
        }
        
        $output->writeln(implode(PHP_EOL, $result));
    }
}
