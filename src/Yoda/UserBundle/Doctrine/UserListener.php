<?php

namespace Yoda\UserBundle\Doctrine;

class UserListener
{
    public function prePersist()
    {
        die('Something is being inserted!');
    }
}
