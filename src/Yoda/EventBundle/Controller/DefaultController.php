<?php

namespace Yoda\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction($count, $firstName)
    {
        $templating = $this->container->get('templating');

        $content = $templating->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );

        return new Response($content);
    }
}
