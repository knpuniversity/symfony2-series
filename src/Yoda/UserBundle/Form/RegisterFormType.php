<?php

namespace Yoda\UserBundle\Form;

use Symfony\Component\Form\AbstractType;

class RegisterFormType extends AbstractType
{
    public function getName()
    {
        return 'user_register';
    }
} 