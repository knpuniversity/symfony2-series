<?php

namespace Yoda\UserBundle\Doctrine;

use Yoda\UserBundle\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;

class UserListener
{
    public function prePersist(LifecycleEventArgs $args)
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
