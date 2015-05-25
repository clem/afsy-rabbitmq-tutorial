<?php

namespace Afsy\Component\AMQP;

use GuzzleHttp\Client as GuzzleClient;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class AfsyDownloadImageConsumer implements ConsumerInterface
{
    /**
     * @var GuzzleHttp\Client $client
     */
    protected $client;

    // Folders
    protected $createFolderMod = 0755;

    /**
     *  Main constructor
     *
     *  @param (GuzzleHttp\Client) $client      Guzzle Client
     *  @param (array) $options                 Array of options
     *
     *  @return (void)
     */
    public function __construct(GuzzleClient $client, $options = array())
    {
        // Initialize
        $this->client = $client;

        // Initialize options
        $this->createFolderMod = isset($options['createFolderMod']) ? $options['createFolderMod'] : $this->createFolderMod;
    }

    /**
     *  Main execute method
     *  Execute actions for a given message
     *
     *  @param (AMQPMessage) $msg       An instance of `PhpAmqpLib\Message\AMQPMessage` with the $msg->body being the data sent over RabbitMQ.
     *
     *  @return (boolean) Execution status (true if everything's of, false if message should be re-queued)
     */
    public function execute(AMQPMessage $msg)
    {
        // Initialize
        $imageToDownload = unserialize($msg->body);

        // Download image
        if(!$this->downloadImageTo($imageToDownload['url'], $imageToDownload['savePath'])) {
            // Image should be downloaded again
            return false;
        }

        // Update saved html file
        $savedHtmlFileContent = file_get_contents($imageToDownload['savedHtmlFile']);

        // Update images paths
        $savedHtmlFileContent = str_replace($imageToDownload['url'], $imageToDownload['savePath'], $savedHtmlFileContent);

        // Save file
        return file_put_contents($imageToDownload['savedHtmlFile'], $savedHtmlFileContent);
    }

    /**
     *  Download an image to a given path
     *
     *  @param (string) $downloadImagePath          Download image path
     *  @param (string) $saveImagePath              Save image path
     *
     *  @return (boolean) Download status (or true if file already exists)
     */
    protected function downloadImageTo($downloadImagePath, $saveImagePath)
    {
        // Initialize
        $saveImageFolder = pathinfo($saveImagePath, PATHINFO_DIRNAME);
        $saveStatus = false;

        // Check if image already exists
        if(file_exists($saveImagePath)) {
            echo 'File "'.$saveImagePath.'" already exists'."\n";
            return true;
        }

        // Check if folder already exists
        if(!is_dir($saveImageFolder)) {
            // Initialize
            $createFolderMod = is_int($this->createFolderMod) ? $this->createFolderMod : intval($this->createFolderMod);

            // Create folder
            mkdir($saveImageFolder, $createFolderMod, true);
            echo 'Folder "'.$saveImageFolder.'" has been created.'."\n";
        }

        // Download image
        try {
            // Log download status
            echo 'Begin download of "'.$downloadImagePath.'".'."\n";

            // Get image content
            $imageContent = $this->client->get($downloadImagePath);

            // Check content
            if(!$imageContent || $imageContent->headers['Status-Code'] == '404') {
                throw new \Exception('Error downloading file "'.$downloadImagePath.'" : returns a void content or a 404 page.', 1);
            }

            // Save image
            $saveStatus = file_put_contents($saveImagePath, $imageContent);

            // Log info
            echo 'Image "'.$saveImagePath.'" has been successfully downloaded!'."\n";

        }
        catch (\Exception $e) {
            // Log error
            echo '#ERROR# Image "'.$downloadImagePath.'" was not downloaded! '."\n";
        }

        // Return save status
        return $saveStatus;
    }

}
