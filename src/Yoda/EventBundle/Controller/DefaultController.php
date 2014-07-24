<?php

namespace Yoda\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction($count, $firstName)
    {
        $arr = array(
            'firstName' => $firstName,
            'count'     => $count,
            'status'    => 'It\'s a traaaaaaaap!',
        );

        $response = new Response(json_encode($arr));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
