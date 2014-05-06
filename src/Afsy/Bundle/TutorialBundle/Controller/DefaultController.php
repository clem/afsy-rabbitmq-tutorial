<?php

namespace Afsy\Bundle\TutorialBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('AfsyTutorialBundle:Default:index.html.twig', array('name' => $name));
    }

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
