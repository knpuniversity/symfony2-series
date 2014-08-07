<?php

namespace Yoda\EventBundle\Twig;

use Yoda\EventBundle\Util\DateUtil;

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

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('ago', array($this, 'calculateAgo')),
        );
    }

    public function calculateAgo(\DateTime $dt)
    {
        return DateUtil::ago($dt);
    }
} 