<?php

namespace Afsy\Component;

use Afsy\Component\Curl\Curl;
use Symfony\Component\DomCrawler\Crawler;

class PageHoover
{
    protected $curl = null;
    protected $options = array();
    protected $downloadFolder = null;

    /**
     *  Main constructor
     *  
     *  @param (Curl) $curl         Curl class
     *  @param (array) $options     Options list
     *
     *  @return (void)
     */
    public function __construct(Curl $curl, array $options)
    {
        // Initialize
        $this->curl = $curl;
        $this->options = $options;

        // Initialize options
        $this->downloadFolder = $options['downloadFolder'];
    }

    /**
     *  Download page method
     *
     *  @param (string) $page       Page to download (url)
     *
     *  @return (boolean) Download status
     */
    public function downloadPage($page)
    {
        // Initialize
        $pageParts = pathinfo($page);
        $saveFile = $this->downloadFolder.date('Ymd-His').'-'.$pageParts['filename'].'.htm';

        // Download page
        $pageContent = $this->curl->get($page); 
     
        // Check downloaded content
        if(!$pageContent) { return false; }

        // Save page in downloadFolder
        if(!file_put_contents($saveFile, "\xEF\xBB\xBF".$pageContent->body))
        {
            // Throw error
            throw new \Exception("Error saving file", 1);
        }

        // Initialize crawler
        $crawler = new Crawler($pageContent->body);

        // Get images list
        $images = $crawler->filter('img')->each(function($image, $i) { return $image->attr('src'); });

        // @todo : Download images

        // Return status
        return true;
    }
}
