Friendly Links and Dates in Twig
================================

Let's look at a couple of things that are hiding in those new template files
I created. Open up ``index.html.twig`` and notice the ``for`` tag, which
loops over the ``entities`` variable we're passing to the template:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}
    
    {% for entity in entities %}
        <article>...</article>
    {% endfor %}

You're going to loop over a lot of things in your Twig days, so take a close
look at the syntax.

Generating URLs
---------------

Further down, check out how we link to the "show" page for each event. Instead
of hardcoding the URL, Symfony generates the URL based on the route:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    <a href="{{ path('event_show', {'id': entity.id}) }}">
        {{ entity.name }}
    </a>

Look at the event routes that were generated earlier and find one called
``event_show``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...
    
    event_show:
        pattern:    /{id}/show
        defaults:   { _controller: "EventBundle:Event:show" }

To generate a URL in Twig, we use the Twig ``path`` function.
Its first  argument is the name of the route we're linking to, like ``event_show``.
The second is an array of wildcards in the route. We pass in the actual value
we want for the ``id`` wildcard.

.. code-block:: html+jinja

    <a href="{{ path('event_show', {'id': entity.id}) }}">
        {{ entity.name }}
    </a>

In the browser, you can see how each link generates almost the same URL, but
with a different id portion.

Rendering Dates in a Template
-----------------------------

One more hidden trick. The Event class's ``time`` field is represented
internally by a `PHP DateTime object`_. We saw this in our ``play.php`` file
when we created an event. To actually render that as a string, we use Twig's
`date filter`_:

.. code-block:: html+jinja

    <dd>
        {{ entity.time|date('g:ia / l M j, Y') }}
    </dd>

It transforms dates into another format and the string we pass to it is from
PHP's good ol' fashioned `date function formats`_.

.. _`PHP DateTime object`: http://www.php.net/manual/en/class.datetime.php
.. _`date filter`: http://twig.sensiolabs.org/doc/filters/date.html
.. _`date function formats`: http://www.php.net/manual/en/function.date.php
