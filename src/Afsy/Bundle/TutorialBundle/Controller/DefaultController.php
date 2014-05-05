<?php

namespace Afsy\Bundle\TutorialBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('AfsyTutorialBundle:Default:index.html.twig', array('name' => $name));
    }
}
