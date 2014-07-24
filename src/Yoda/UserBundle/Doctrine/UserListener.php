<?php

namespace Yoda\UserBundle\Doctrine;

use Yoda\UserBundle\Entity\User;

class UserListener
{
    public function prePersist()
    {
        die('Something is being inserted!');
    }

    private function handleEvent(User $user)
    {
        $plainPassword = $user->getPlainPassword();

        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user)
        ;

        $password = $encoder->encodePassword($plainPassword, $user->getSalt());
        $user->setPassword($password);
    }
}
