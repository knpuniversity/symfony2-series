Creating a Pretty CSV Download 
==============================

Buzzword time! Services! Dependency Injection! Dependency Injection Container!

Hold on, because we're about to discover what these terms mean, how *core*
they are to Symfony, and just how simple these things really are.

Pretend that someone needs to be able to download a CSV of all of the events
that have been updated during the last 24 hours. Let's create a whole new
controller class for this called ``ReportController``, since ``EventController``
is getting a bit big::

    // src/Yoda/EventBundle/Controller/ReportController.php
    namespace Yoda\EventBundle\Controller;

    class ReportController extends Controller
    {

    }

Let's create an ``updatedEventsAction`` and use those handy annotation routes.
And of course don't forget to copy in the ``Route`` ``use`` statement for the 
annotation::

    /**
     * @Route("/events/report/recentlyUpdated.csv")
     */
    public function updatedEventsAction()
    {

    }

Try the URL in your browser. If you see the "The controller must return a
response" error like I do, then we're good! This is proof that our controller
is being executed.

Creating a CSV Response
-----------------------

First we need a custom query to find recently updated events. Should we just
put this in our controller? I *hope* you're screaming no. The force is strong enough
in us to now put these directly in our repository class. Create a new ``getRecentlyUpdatedEvents``
method in ``EventRepository`` and build a query that *only* returns events
updated within the last 24 hours::

    // src/Yoda/EventBundle/Entity/EventRepository.php
    // ...

    public function getRecentlyUpdatedEvents()
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.updatedAt > :since')
            ->setParameter('since', new \DateTime('24 hours ago'))
            ->getQuery()
            ->execute()
        ;
    }

Let's call this in the controller. This should be getting boring
because, we always query the same way: get the entity manager, get 
the repository, then call a method on it::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    public function updatedEventsAction()
    {
        $em = $this->getDoctrine()->getManager();

        $events = $em->getRepository('EventBundle:Event')
            ->getRecentlyUpdatedEvents();

        // ...
    }

Now we need to turn these ``Event`` objects into a CSV. I'll write some
manual code for this. Yes, there *are* better ways to create CSV's, but trust
me for a second. This code will help us show off one of Symfony's most
powerful features::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    public function updatedEventsAction()
    {
        $em = $this->getDoctrine()->getManager();

        $events = $em->getRepository('EventBundle:Event')
            ->getRecentlyUpdatedEvents();

        $rows = array();
        foreach ($events as $event) {
            $data = array($event->getId(), $event->getName(), $event->getTime()->format('Y-m-d H:i:s'));

            $rows[] = implode(',', $data);
        }

        $content = implode("\n", $rows);

        // ...
    }

So what does a controller *always* return? A Response object of course! Let's
just create one manually and pass the csv ``$content`` to it::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...
    use Symfony\Component\HttpFoundation\Response;

    public function updatedEventsAction()
    {
        // ...

        $content = implode("\n", $rows);
        $response = new Response($content);
        
        return $response;
    }

Refresh! Gosh, that's the prettiest CSV I've seen all day. Ah, but if I check
the network tab in my browser, the response is ``text/html``. I forgot to
set that pesky ``Content-Type`` header. Let's fix that::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    public function updatedEventsAction()
    {
        // ...

        $content = implode("\n", $rows);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        
        return $response;
    }

This time Chrome sees that it's a CSV and downloads it for me. There's nothing
new so far, but we're writing great code.
