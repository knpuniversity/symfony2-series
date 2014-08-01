Dependency Inject All the Things
================================

The CSV returns the ``id``, ``name`` and ``time`` of each event. Let's pretend
that someone is using this to double-check the accuracy of updated events.
To make their life easier, I want to also return the URL to each event.

So how do we generate URL's? In ``EventController``, we used the ``generateUrl``
function::

    $this->generateUrl('event_show', array('slug' => $entity->getSlug()))

So let's try putting that into ``EventReportManager`` and seeing what happens::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    public function getRecentlyUpdatedReport()
    {
        // ...

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

Let's try it. Ah, no download - just an ugly error:

    Call to undefined method Yoda\EventBundle\Reporting\EventReportManager::generateUrl()

We made this mistake before - ``generateUrl`` lives in Symfony's ``Controller``,
and we don't have access to it here. Open up that function to remember what
it *actually* does::

    // vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
    // ...

    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')
            ->generate($route, $parameters, $referenceType);
    }

This tells me that if I want to generate a URL, I *actually* need the ``router``
service. So how can we get the ``router`` service inside ``EventReportManager``?
You know the secret: dependency injection.

Add a *second* constructor argument and a second class property::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    use Doctrine\ORM\EntityManager;
    use Symfony\Component\Routing\Router;

    class EventReportManager
    {
        private $em;

        private $router;

        public function __construct(EntityManager $em, Router $router)
        {
            $this->em = $em;
            $this->router = $router;
        }

        // ...
    }

This time, I guessed the ``router`` class name for the type-hint. Now that
we have the ``router``, just use it in the function::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    public function getRecentlyUpdatedReport()
    {
        // ...

        foreach ($events as $event) {
            $data = array(
                $event->getId(),
                $event->getName(),
                $event->getTime()->format('Y-m-d H:i:s'),
                $this->router->generate('event_show', array('slug' => $event->getSlug()))
            );

            $rows[] = implode(',', $data);
        }

        return implode("\n", $rows);
    }

Ok, let's test it. Great, now we get a different error:

    Catchable Fatal Error: Argument 2 passed to
    Yoda\EventBundle\Reporting\EventReportManager::__construct() must be
    an instance of Symfony\Component\Routing\Router, none given

Read it closely. It says that something is calling ``__construct`` on our
class but passing it nothing for the second argument. Of course: we forgot
to tell the container about this new argument. Open the ``services.yml``
file and add a second item to ``arguments``:

.. code-block:: yaml

    services:
        event_report_manager:
            class: Yoda\EventBundle\Reporting\EventReportManager
            arguments: ["@doctrine.orm.entity_manager", "@router"]

*Now*, we get the download again. Open up the CSV. Hey, we have URL's!

.. code-block:: text

    5,Darth's Birthday Party!,2014-07-24 12:00:00,/darth-s-birthday-party/show
    6,Rebellion Fundraiser Bake Sale!,2014-07-24 12:00:00,/rebellion-fundraiser-bake-sale/show

Woops! The URLs aren't helpful unless they're absolute. Pass ``true`` as
the third argument to ``generate`` to make this happen::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    $data = array(
        $event->getId(),
        $event->getName(),
        $event->getTime()->format('Y-m-d H:i:s'),
        $this->router->generate(
            'event_show',
            array('slug' => $event->getSlug()),
            true
        )
    );

Download another file and open it up. Perfect!

Here are the *huge* takeaways. When you're in a service and you need to do
some work, just find out which service does that work, inject it through
the constructor, then use it. You'll use this pattern over and over again.
Understand this, and you've mastered the most important concept in Symfony.
