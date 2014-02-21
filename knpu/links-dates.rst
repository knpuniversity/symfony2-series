Friendly Links and Dates in Twig
================================

Before we move on, let's look at one of the new templates - ``index.html.twig``.
This template uses HTML5 tags, which isn't important, so don't worry if you're
not used to them. First, notice the ``for`` tag. This loops through an ``entities``
array that's passed to the template:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}
    
    {% for entity in entities %}
        <article>...</article>
    {% endfor %}

Further down look at how we link to the "show" page of each event. Instead
of hardcoding it, we'll let Symfony generate the URL from one of our routes.
If you look at the event routes that were generated earlier, you'll see one
called ``event_show`` that renders the ``show`` action. The route has an
``id`` wildcard, which we'll fill in with each event's id.

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...
    
    event_show:
        pattern:    /{id}/show
        defaults:   { _controller: "EventBundle:Event:show" }

To generate a URL in Twig, we can use the Twig ``path`` function. The first 
argument is the name of the route we're linking to. The second is an array
of variables - we use it to pass in a real value for the ``id`` wildcard:

.. code-block:: html+jinja

    <a href="{{ path('event_show', {'id': entity.id}) }}">
        {{ entity.name }}
    </a>

In the browser, you can see how each link generates almost the same URL, but
with a different id portion.

Rendering Dates in a Template
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let's look at one last thing. The Event class's ``date`` field is represented
internally by a `PHP DateTime object`_. We saw this back in our play script
when we were creating new events. To actually render that as a string, we
can use Twig's `date filter`_. This takes a date and transforms it into a
string, based on the format we want. The format here uses the same format
as the good ol' fashioned `PHP date function`_:

.. code-block:: html+jinja

    <dd>
        {{ entity.time|date('g:ia / l M j, Y') }}
    </dd>

So, we didn't do a lot of work in this chapter, but we generated a ton of
code and went through a lot more of Symfony's core features. With the power
of code generators, you should feel like you can really get things done
quickly.

.. _`PHP DateTime object`: http://www.php.net/manual/en/class.datetime.php
.. _`date filter`: http://twig.sensiolabs.org/doc/filters/date.html
.. _`PHP date function`: http://www.php.net/manual/en/function.date.php
