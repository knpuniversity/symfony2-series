<?php

namespace Yoda\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Yoda\UserBundle\Entity\User;

class LoadUsers implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('darth');
        // todo - fill in this encoded password... ya know... somehow...
        $user->setPassword('');
        $manager->persist($user);

        // the queries aren't done until now
        $manager->flush();
    }
}
