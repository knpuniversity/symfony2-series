<?php

namespace Yoda\UserBundle\Doctrine;

use Yoda\UserBundle\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;

class UserListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof User) {
            $this->handleEvent($entity);
        }
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
