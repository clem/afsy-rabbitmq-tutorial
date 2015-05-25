<?php

namespace Afsy\Bundle\TutorialBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     *  Download action
     */
    public function downloadAction()
    {
        // Initialize
        $pageHoover = $this->container->get('afsy.pagehoover');

        // Download page
        $page = 'http://afsy.fr/';
        $pageHoover->downloadPage($page);

        // Return status
        $response = new Response();

        return $response->setContent('Page "'.$page.'" is downloaded !')->send();
    }
}
