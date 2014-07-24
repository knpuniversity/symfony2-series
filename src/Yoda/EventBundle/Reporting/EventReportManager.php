<?php

namespace Yoda\EventBundle\Reporting;

use Doctrine\ORM\EntityManager;

class EventReportManager
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecentlyUpdatedReport()
    {
        $events = $this->em->getRepository('EventBundle:Event')
            ->getRecentlyUpdatedEvents();

        $rows = array();
        foreach ($events as $event) {
            $data = array(
                $event->getId(),
                $event->getName(),
                $event->getTime()->format('Y-m-d H:i:s'),
                $this->generateUrl('event_show', array('slug' => $event->getSlug()))
            );

            $rows[] = implode(',', $data);
        }

        return implode("\n", $rows);
    }
} 