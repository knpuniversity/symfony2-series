<?php

namespace Yoda\EventBundle\Twig;

class EventExtension extends \Twig_Extension
{
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'event';
    }
} 