<?php

namespace Yoda\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Yoda\UserBundle\Entity\User;

class RegisterController extends Controller
{
    /**
     * @Route("/register", name="user_register")
     * @Template
     */
    public function registerAction(Request $request)
    {
        $defaultData = array(
            'username' => 'Leia',
        );

        $form = $this->createFormBuilder($defaultData, array(
            'data_class' => 'Yoda\UserBundle\Entity\User',
        ))
            ->add('username', 'text')
            ->add('email', 'email')
            ->add('password', 'repeated', array(
                'type' => 'password',
            ))
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isValid()) {
            $user = $form->getData();

            $user->setPassword(
                $this->encodePassword($user, $user->getPassword())
            );

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $url = $this->generateUrl('event');

            return $this->redirect($url);
        }

        return array('form' => $form->createView());
    }

    private function encodePassword(User $user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user)
        ;

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }
}
