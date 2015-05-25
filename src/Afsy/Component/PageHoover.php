<?php

namespace Afsy\Component;

use Afsy\Component\Curl\Curl;
use Symfony\Component\DomCrawler\Crawler;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

class PageHoover
{
    /**
     *  @var Afsy\Component\Curl\Curl
     */
    protected $curl = null;

    /**
     *  @var array
     */
    protected $options = array();

    /**
     *  @var string
     */
    protected $downloadFolder = null;

    /**
     *  @var OldSound\RabbitMqBundle\RabbitMq\Producer
     */
    protected $downloadImageProducer = null;

    /**
     *  Main constructor
     *
     *  @param (Curl) $curl                         Curl class
     *  @param (Producer) $downloadImageProducer    Download image producer
     *  @param (array) $options                     Options list
     *
     *  @return (void)
     */
    public function __construct(Curl $curl, Producer $downloadImageProducer, array $options)
    {
        // Initialize
        $this->curl = $curl;
        $this->options = $options;
        $this->downloadImageProducer = $downloadImageProducer;

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
        $downloadFolder = $this->downloadFolder;
        $saveFile = $downloadFolder.date('Ymd-His').'-'.$pageParts['filename'].'.htm';

        // Download page
        $pageContent = $this->curl->get($page);

        // Check downloaded content
        if(!$pageContent) {
            return false;
        }

        // Save page in downloadFolder
        if(!file_put_contents($saveFile, "\xEF\xBB\xBF".$pageContent->body)) {
            // Throw error
            throw new \Exception("Error saving file", 1);
        }

        // Initialize crawler
        $crawler = new Crawler($pageContent->body);

        // Get images list
        $images = $crawler->filter('img')->each(function(Crawler $image, $i) {
            return $image->attr('src');
        });

        // Download images
        foreach ($images as $image) {
            // Initialize
            $image = str_replace(' ', '', $image);
            $imgExt = pathinfo($image, PATHINFO_EXTENSION);
            $hasHost = filter_var($image, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);

            // Check host
            if(!$hasHost) { $image = $pageParts['dirname'].$image; }

            // Check extension
            if(!in_array($imgExt, array('png', 'jpg', 'jpeg', 'gif'))){ $imgExt = 'png'; }

            // Create image to publish
            $imgToPublish = array(
                'url' => $image,
                'savePath' => $this->downloadFolder.pathinfo($image, PATHINFO_FILENAME).'.'.$imgExt,
                'savedHtmlFile' => $saveFile,
            );

            // Publish image
            $sImg = serialize($imgToPublish);
            $this->downloadImageProducer->publish($sImg);
        }

        // Return status
        return true;
    }
}
