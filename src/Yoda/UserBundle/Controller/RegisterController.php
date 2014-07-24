<?php

namespace Yoda\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class RegisterController extends Controller
{
    /**
     * @Route("/register", name="user_register")
     */
    public function registerAction()
    {
        $form = $this->createFormBuilder()
            ->add('username', 'text')
            ->add('email', 'text')
            ->add('password', 'password')
            ->getForm()
        ;
    }
}
