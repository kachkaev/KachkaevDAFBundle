<?php
namespace Kachkaev\PostgresHelperBundle\Helper;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Console\Helper\ProgressHelper;

/**
 * Downloads files using curl and shows download progress in console on demand
 *
 * @author  "Alexander Kachkaev <alexander@kachkaev.ru>"
 *
 * @DI\Service("pr.helper.file_downloader")
 */

class FileDownloader {

    protected $progressHelper;
    protected $ch;
    
    public function __construct()
    {
        $this->ch = curl_init();
        $this->progressHelper = new ProgressHelper();
        $this->progressHelper->setFormat('[%bar%] %percent%% %current% Bytes');
    }
    
    public function download($source, $target, $output, $showProgress = true)
    {

        $progressHelper = $this->progressHelper;
        $progressHelperStatus = [];
        $progressHelperStatus['state'] = 0;
        $progressHelperStatus['progress'] = 0;
        
        $callback = function ($download_size, $downloaded, $upload_size, $uploaded) use (&$progressHelper, &$output, &$progressHelperStatus, &$showProgress)
        {
            if (!$showProgress) {
                return;
            }
            
            if ($download_size === 0 || $downloaded === 0) {
                return;
            }
        
            if (!$progressHelperStatus['state']) {
                $progressHelper->start($output, $download_size);
                $progressHelperStatus['state'] = 1;
                $progressHelper->advance($downloaded - $progressHelperStatus['progress']);
            } elseif ($progressHelperStatus['state'] == 1) {
                $progressHelper->advance($downloaded - $progressHelperStatus['progress']);
                if ($downloaded === $download_size) {
                    $progressHelperStatus['state'] = 2;
                    $progressHelper->finish();
                }
            }
            $progressHelperStatus['progress'] = $downloaded;
        };
        
        $options = array(
                CURLOPT_FILE => fopen($target, 'w'),
                CURLOPT_TIMEOUT => 28800, // set this to 8 hours so we don't timeout on big files
                CURLOPT_URL => $source,
                CURLOPT_PROGRESSFUNCTION => $callback,
                CURLOPT_NOPROGRESS => false,
                CURLOPT_BUFFERSIZE => 1024*1024,
        );
        curl_setopt_array($this->ch, $options);
        curl_exec($this->ch);
    }
}