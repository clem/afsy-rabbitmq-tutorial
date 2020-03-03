<?php

namespace Afsy\Component;

use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

class PageHoover
{
    /**
     *  @var GuzzleClient
     */
    protected $client = null;

    /**
     *  @var array
     */
    protected $options = [];

    /**
     *  @var string
     */
    protected $downloadFolder = null;

    /**
     *  @var Producer
     */
    protected $downloadImageProducer = null;

    /**
     *  Main constructor.
     *
     *  @param (GuzzleClient) $client               Guzzle Client
     *  @param (Producer) $downloadImageProducer    Download image producer
     *  @param (array) $options                     Options list
     *
     *  @return (void)
     */
    public function __construct(GuzzleClient $client, Producer $downloadImageProducer, array $options)
    {
        // Initialize
        $this->client = $client;
        $this->options = $options;
        $this->downloadImageProducer = $downloadImageProducer;

        // Initialize options
        $this->downloadFolder = $options['downloadFolder'];
    }

    /**
     *  Download page method.
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
        $res = $this->client->get($page);

        // Check downloaded content
        if ($res->getStatusCode() !== 200) {
            return false;
        }

        // Get page content
        $pageContent = $res->getBody()->getContents();

        // Save page in downloadFolder
        if (!file_put_contents($saveFile, "\xEF\xBB\xBF".$pageContent)) {
            // Throw error
            throw new \Exception('Error saving file', 1);
        }

        // Initialize crawler
        $crawler = new Crawler($pageContent);

        // Get images list
        $images = $crawler->filter('img')->each(function(Crawler $image) {
            return $image->attr('src');
        });

        // Download images
        foreach ($images as $image) {
            // Initialize
            $image = str_replace(' ', '', $image);
            $imgExt = pathinfo($image, PATHINFO_EXTENSION);
            $hasHost = filter_var($image, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);

            // Check host
            if (!$hasHost) {
                $image = $pageParts['dirname'].$image;
            }

            // Check extension
            if (!in_array($imgExt, ['png', 'jpg', 'jpeg', 'gif'])) {
                $imgExt = 'png';
            }

            // Create image to publish
            $imgToPublish = [
                'url' => $image,
                'savePath' => $this->downloadFolder.pathinfo($image, PATHINFO_FILENAME).'.'.$imgExt,
                'savedHtmlFile' => $saveFile,
            ];

            // Publish image
            $sImg = serialize($imgToPublish);
            $this->downloadImageProducer->publish($sImg);
        }

        // Return status
        return true;
    }
}
