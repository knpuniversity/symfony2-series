<?php

namespace Yoda\EventBundle\Reporting;

class EventReportManager
{
    private $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function getRecentlyUpdatedReport()
    {
        $events = $this->em->getRepository('EventBundle:Event')
            ->getRecentlyUpdatedEvents();

        $rows = array();
        foreach ($events as $event) {
            $data = array($event->getId(), $event->getName(), $event->getTime()->format('Y-m-d H:i:s'));

            $rows[] = implode(',', $data);
        }

        return implode("\n", $rows);
    }
} 