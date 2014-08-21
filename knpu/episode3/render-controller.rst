Render another Controller in Twig
=================================

When a user sees our 404 page, I'd *love* it if we could show them a list of
upcoming events. Hmm, but that's not possible. Normally, I'd query for some
events and then pass them into my template. But we don't have access to Symfony's
core controller that's rendering ``error404.html.twig``. 

Whenever you're in a template and don't have access to something you need,
there's a sure-fire solution: use the Twig ``render`` function. This lets
you call any controller function you want and prints the results.

Create an Embedded Controller
-----------------------------

Start by adding a new controller function that queries for upcoming events,
and renders a template. So far, this feels like any other controller, except
it doesn't have a route::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function _upcomingEventsAction()
    {
        $em = $this->getDoctrine()->getManager();

        $events = $em->getRepository('EventBundle:Event')
            ->getUpcomingEvents()
        ;

        return $this->render('EventBundle:Event:_upcomingEvents.html.twig', array(
            'events' => $events,
        ));
    }

Create the template and grab the event-rendering code from ``index.html.twig``.
But hey, *don't* extend the base layout. This controller is meant to just
render "part" of a page, not the entire HTML body. I also need to rename
``entities`` to ``events``, since that's how I called the variable in the
controller:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/_upcomingEvents.html.twig #}
    {% for event in events %}
        <article>
            <header class="map-container">
                <img src="http://maps.googleapis.com/maps/api/staticmap?center={{ event.location | url_encode }}&markers=color:red%7Ccolor:red%7C{{ event.location | url_encode }}&zoom=14&size=150x150&maptype=roadmap&sensor=false" />
            </header>
            <section>
                <h3>
                    <a href="{{ path('event_show', {'slug': event.slug}) }}">{{ event.name }}</a>
                </h3>

                <dl>
                    <dt>where:</dt>
                    <dd>{{ event.location }}</dd>

                    <dt>when:</dt>
                    <dd>{{ event.time | date('g:ia / l M j, Y') }}</dd>

                    <dt>who:</dt>
                    <dd>Todo # of people</dd>
                </dl>
            </section>
        </article>
    {% endfor %}

The new controller prints *just* a list of events, without a layout. We
didn't give it a route, but we don't need to: we're going to call it straight
from Twig.

Oh, and what's up with the underscore in front of the name? That's just a
standard I follow for controllers that render partial pages.

Getting render-happy in Twig
----------------------------

Ok, *now* I'll show you the power behind this render weapon. Remove the query
in ``indexAction`` and pass nothing into the template::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    /**
     * @Template()
     * @Route("/", name="event")
     */
    public function indexAction()
    {
        return array();
    }

Next, remove the big ``entities`` for loop that we just copied from ``index.html.twig``
and replace it with the ``render`` function:

.. code-block:: html+jinja

    {% extends 'EventBundle::layout.html.twig' %}

    {% block body %}
        <section class="events">
            {# same <header> stuff as before #}
            {# ... #}

            {{ render(controller('EventBundle:Event:_upcomingEvents')) }}
        </section>
    {% endblock %}

Try out the homepage in the ``dev`` environment. Hey, it looks just like before!
``render`` calls our controller, we build a partial HTML page, and then it
gets printed. This handy function is great for re-using page chunks and is
also key to using `Symfony's Caching`_.

.. tip::

    If you just want to re-use a Twig template, use the `include`_ function.

Using render in the Error Template
----------------------------------

Our goal was to list upcoming events on the 404 page. Well, that's pretty easy
now:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error404.html.twig #}
    {# ... #}

    {% block body %}
        {# existing <section> ... #}

        <section class="events">
            {{ render(controller('EventBundle:Event:_upcomingEvents')) }}
        </section>
    {% endblock %}

Move to an imaginary page in your ``prod`` environment. In other words, put
the ``app.php`` back in the URL:

    http://localhost:9000/app.php/foo

Ah, but don't forget to clear your cache!

.. code-block:: bash

    php app/console cache:clear --env=prod

Controller Arguments
--------------------

Great! Now what if we wanted to show a different number of upcoming
events on the homepage versus the error page? No problem: ``render`` let's 
us pass arguments to the controller function. Pass a ``max`` argument
of ``1`` from the error template:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error404.html.twig #}
    {# ... #}

    <section class="events">
        {{ render(controller('EventBundle:Event:_upcomingEvents', {
            'max': 1
        })) }}
    </section>

Next, add a ``$max`` argument to ``_upcomingEventsAction`` and give it a
default value so that we don't *have* to pass it in. Send this variable into
the ``getUpcomingEvents()`` function::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function _upcomingEventsAction($max = null)
    {
        $em = $this->getDoctrine()->getManager();

        $events = $em->getRepository('EventBundle:Event')
            ->getUpcomingEvents($max)
        ;

        return $this->render('EventBundle:Event:_upcomingEvents.html.twig', array(
            'events' => $events,
        ));
    }

In ``EventRepository``, give the function a ``$max`` argument. Instead of
returning immediately, set the query builder to a variable and then return
it later. If ``$max`` is set, limit the number of results that will be returned::

    // src/Yoda/EventBundle/Entity/EventRepository.php
    // ...

    public function getUpcomingEvents($max = null)
    {
        $qb = $this
            ->createQueryBuilder('e')
            ->addOrderBy('e.time', 'ASC')
            ->andWhere('e.time > :now')
            ->setParameter('now', new \DateTime());

        if ($max) {
            $qb->setMaxResults($max);
        }

        return $qb
            ->getQuery()
            ->execute()
        ;
    }

Clear your cache and then try it out. Hey, only 1 event! Not only can ``render``
call a controller, but we can control its arguments. Now you're unstoppable.

.. _`render a template`: http://bit.ly/sf2-extra-template
.. _`Symfony's Caching`: http://symfony.com/doc/current/book/http_cache.html
.. _`include`: http://twig.sensiolabs.org/doc/functions/include.html
