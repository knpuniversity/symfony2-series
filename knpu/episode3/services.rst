Your Very First Service
-----------------------

Create a new ``Reporting`` directory in the bundle and a new ``EventReportManager``
class inside of it::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    namespace Yoda\EventBundle\Reporting;
    
    class EventReportManager
    {
    }

Like any other class, give it the right namespace. 

But, unlike entiies, forms and controllers, this class is special because
it has *absolutely* nothing to do with Symfony. It's just a "plain-old-PHP-object"
that we'll use to help organize our own code.

Create a ``getRecentlyUpdatedReport`` method to the class and paste the logic
from our controller that creates the CSV text::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    class EventReportManager
    {
        public function getRecentlyUpdatedReport()
        {
            $em = $this->getDoctrine()->getManager();

            $events = $em->getRepository('EventBundle:Event')
                ->getRecentlyUpdatedEvents();

            $rows = array();
            foreach ($events as $event) {
                $data = array($event->getId(), $event->getName(), $event->getTime()->format('Y-m-d H:i:s'));

                $rows[] = implode(',', $data);
            }

            return implode("\n", $rows);
        }
    }

To use it in ``ReportController``, creat a new instance of ``EventReportManager``
and call ``getRecentlyUpdatedReport`` on it::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...
    
    use Yoda\EventBundle\Reporting\EventReportManager;

    public function updatedEventsAction()
    {
        $eventReportManager = new EventReportManager();
        $content = $eventReportManager->getRecentlyUpdatedReport();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }

And hey! Don't forget the ``use`` statement when referencing the class.

So why am I making you do this? Remember how we put our queries in repository
classes? That's cool because it keeps things organized and we can also re-use
those queries.

We're doing the same exact thing, but for reporting code instead of queries.
Inside ``EventReportManager``, the reporting code is reusable and organized
in one spot.

DependencyInjection to the Rescue!
----------------------------------

But don't get too excited, I broke our app. Sorry. Refresh to see the error:

.. highlights::

    Call to undefined method Yoda\EventBundle\Reporting\EventReportManager::getDoctrine()

We're calling ``$this->getDoctrine()``. That function lives in Symfony's base
Controller. But in ``EventReportManager``, we don't extend anything and we
don't magically have access to this Doctrine object.

The code inside ``EventReportManager`` is *dependent* on this "doctrine"
object. Well, more specifically, it's dependent on Doctrine's entity manager.

The fix for our puzzle is to "inject the dependency", or to use "dependency injection".
That's a very scary term for a really simple idea.

First, add a constructor method with a single ``$em`` argument. Set that on
a new ``$em`` class property::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    class EventReportManager
    {
        private $em;
        
        public function __construct($em)
        {
            $this->em = $em;
        }

        // ...
    }

This will be the entity manager object. Inside ``getRecentlyUpdatedReport``,
use the new ``$em`` property and remove the non-existent ``getDoctrine`` call::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...
    
    private $em;
    // ...

    public function getRecentlyUpdatedReport()
    {
        $events = $this->em->getRepository('EventBundle:Event')
            ->getRecentlyUpdatedEvents();

        // ...
    }

Back in ``ReportController``, get the entity manager like we always do and
pass it as the first argument when creating a new ``EventReportManager``::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    use Yoda\EventBundle\Reporting\EventReportManager;

    public function updatedEventsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $eventReportManager = new EventReportManager($em);
        $content = $eventReportManager->getRecentlyUpdatedReport();

        // ...
    }

Refresh! Yes! The CSV has downloaded!

You deserve some congrats. You've just done "dependency injection". It's
not some new programming practice or magic trick, it's just the idea of passing
dependencies into objects that need them. For us, ``EventReportManager``
needs the entity manager object. So when we create the manager, we just "inject"
it by passing it to the constructor. Now that the manager has everything
it needs, it can get its work done.

.. tip::

    To learn more, check out our free tutorial that's all about the great
    topic of `Dependency Injection`_.

So What's a Service?
--------------------

And you know what else? We also just created our first "service". Yes, we're
hitting multiple buzzwords at once!

A "service" is a term that basically refers to any object that does some
work for us. ``EventReportManager`` generates a CSV, so it's a "service".

So what's an object that's *not* a service? How about an entity. They don't
really *do* anything, they just hold data. If you code well, you'll notice
that every class fits into one of these categories. A class either does work
but doesn't hold much data, like a service, or it holds data but doesn't
do much, like an entity.

Another common property of a "service" class is that you only ever need one
instance at a time. If we needed to generate 2 CSV reports, it wouldn't really
make sense to instantiate 2 ``EventReportManager`` objects when we can just
re-use the same one twice. "Services" are the machines of your app: each
does its own "work", like creating reports, sending emails, or anything else
you can dream up.

.. _`Dependency Injection`: http://knpuniversity.com/screencast/dependency-injection
