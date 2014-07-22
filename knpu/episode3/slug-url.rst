Using the slug in the Event URL
===============================

We've got slugs! So let's enjoy them by putting them into our URLS! 

First, change the ``event_show`` route to use the ``slug`` instead
of the ``id``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...

    event_show:
        pattern:  /{slug}/show
        defaults: { _controller: "EventBundle:Event:show" }

    # ...

You can also update the other routes if you want to - but this is the most
important URL to get right.

Update the ``showAction`` accordingly and query for the ``Event`` using the
slug::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function showAction($slug)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EventBundle:Event')
            ->findOneBy(array('slug' => $slug));

        // ...

        // also change this line, since the $id variable is gone
        $deleteForm = $this->createDeleteForm($entity->getId());
        // ...
    }

And with those 2 small changes, this page should work!

Updating the URL generation
---------------------------

Head over to the homepage to try it. Ah, a *huge* error:

    An exception has been thrown during the rendering of a template
    ("Some mandatory parameters are missing ("slug") to generate a URL for
    route "event_show".")

The ``event_show`` route now has a ``slug`` wildcard instead of ``id``. So
wherever we're generating a URL to this route, we need to change the wildcard
we're passing to it.

I'll use the "git grep" command to figure out where we're using this route:

.. code-block:: bash

    git grep event_show

Update each to pass in the ``slug`` instead of the ``id``::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ..

    public function createAction(Request $request)
    {
        // ...

        return $this->redirect($this->generateUrl(
            'event_show', array('slug' => $entity->getSlug())
        ));
    }

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    <a href="{{ path('event_show', {'slug': entity.slug}) }}">{{ entity.name }}</a>

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/edit.html.twig #}
    {# ... #}

    <a class="link" href="{{ path('event_show', {'slug': entity.slug}) }}">show event</a>

Refresh the homepage. Nice! When we click on an event, we have a beautiful URL.
