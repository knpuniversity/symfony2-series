Creating a Custom orderBy Query
===============================

Right now, the homepage lists every event in the order they were added to
the database. We can do better! Head to ``EventController`` and replace the
``findAll`` method with a custom query that orders the events by the ``time``
property, so we can see the events that are coming up next at the top::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
    
        $entities = $em
            ->getRepository('EventBundle:Event')
            ->createQueryBuilder('e')
            ->addOrderBy('e.time', 'ASC')
            ->getQuery()
            ->execute()
        ;

        // ...
    }

When we check the homepage, it looks about the same as before. Let's complicate
things by only showing upcoming events::

    $entities = $em
        ->getRepository('EventBundle:Event')
        ->createQueryBuilder('e')
        ->addOrderBy('e.time', 'ASC')
        ->andWhere('e.time > :now')
        ->setParameter('now', new \DateTime())
        ->getQuery()
        ->execute()
    ;

This uses the parameter syntax we saw before and uses a ``\DateTime`` object
to only show events after right now.

To test this, edit one of the events and set its time to a date in the past.
Back on the homepage, the event is now missing from the list!

Moving Queries to the Repository
--------------------------------

This is great, but what if we want to reuse this query somewhere else? Instead
of keeping the query in the controller, create a new method inside ``EventRepository``
and move it there::

    // src/Yoda/EventBundle/Entity/EventRepository.php
    // ...

    /**
     * @return Event[]
     */
    public function getUpcomingEvents()
    {
        return $this
            ->createQueryBuilder('e')
            ->addOrderBy('e.time', 'ASC')
            ->andWhere('e.time > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute()
        ;
    }

Now that we're actually inside the repository, we just start by calling the
``createQueryBuilder()``. In the controller, continue to get the repository,
but now just call ``getUpcomingEvents`` to use the method::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em
            ->getRepository('EventBundle:Event')
            ->getUpcomingEvents()
        ;

        // ...
    }

.. note::

    The ``$em->getRepository('EventBundle:Event')`` returns our ``EventRepository``
    object.

We saw this before in `episode 2`_, but it's so important that I wanted to show
it again. Whenever you have a custom query: create a new method in the right
repository class and build it there. This keeps all of our queries organized,
makes them reusable, and makes our controllers readable.

We now have a "skinny" controller, which means that we're doing a good job
of organizing any logic we need in other classes. It also means that you
can show your code to fellow programmers and impress them with your well-organized
Jedi ways.

.. _`episode 2`: http://knpuniversity.com/screencast/symfony2-ep2/repository
