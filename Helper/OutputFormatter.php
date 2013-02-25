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
    public function outputArrayAsAlignedList(OutputInterface $output, $data, $tags = ['info', ''], $fixedWidths = null, $stringSpacings = null)
    {
        $calculatedWidths = [];
        
        $processedData = [];
        
        if (!is_array($stringSpacings)) {
            $stringSpacings = [];
        }
        
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
                if (!array_key_exists($k, $calculatedWidths) || strlen($v) > $calculatedWidths[$k]) {
                    $calculatedWidths[$k] = strlen($v);
                }
            }
        }
        
        // Supplementing calculated widths with fixed widths
        if (is_array($fixedWidths)) {
            foreach($fixedWidths as $k => $v) {
                $calculatedWidths[$k] = $v;
            }
        }

        // Generating output
        $result = [];
        foreach($processedData as $currentProcessedData) {
            $currentResult = '';
            foreach ($currentProcessedData as $k => $v) {
                $calculatedWidth = $calculatedWidths[$k];
                if ($calculatedWidth === null) {
                    continue;
                }
                if (strlen($v) > $calculatedWidth) {
                    if ($calculatedWidth > 4) {
                        $v = substr($v, 0, $calculatedWidth - 3) . '...';
                    } elseif ($calculatedWidth > 1) {
                        $v = substr($v, 0, $calculatedWidth - 1) . '.';                        
                    } else {
                        $v = substr($v, 0, 1);                        
                    }
                }
                if (array_key_exists($k, $tags) && $tags[$k]) {
                    $currentResult .= sprintf('<%s>%s</%s>', $tags[$k], $v, $tags[$k]); 
                } else {
                    $currentResult .= $v;
                }
                if ($k != sizeof($currentProcessedData) - 1) {
                    $stringSpacing = $this->defaultListSpacing;
                    if (array_key_exists($k, $stringSpacings) && is_numeric($stringSpacings[$k])) {
                        $stringSpacing = $stringSpacings[$k];
                    }
                    $currentResult .= str_repeat(' ', $calculatedWidth - strlen($v) + $stringSpacing);
                }
            }
            $result []= $currentResult;
        }
        
        $output->writeln(implode(PHP_EOL, $result));
    }
}
