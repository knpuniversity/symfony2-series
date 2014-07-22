Twig Mind Tricks
================

I've got 2 more bonuses from Twig. In every template, you have access to
a variable called ``app``. This has a bunch of useful things on it, like
the request, the security context, the User object, and the session:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    {% block body %}
        {# some examples - remove these after you try them #}
        {{ app.session.get('some_session_key') }}
        {{ app.request.host }}

        {# ... #}
    {% endblock %}

It's actually an object called `GlobalVariables`_, which you can check out
yourself. So when you need one of these things, remember ``app``!

.. tip::

    Remove this code after trying it out - it's just an example of how you
    can access the request and session data - it doesn't add anything real
    to our project.

The block Twig Function
-----------------------

Next, head to our base template. Right now, the title tag is boring: I can
either replace it entirely in a child template or use the default:

.. code-block:: html+jinja

    <title>{% block title %}Welcome!{% endblock %}</title>

Let's make this better by adding a little suffix whenever the page title is
overridden. This shows off the `block function`_, which gives us the current
value of a block:

.. code-block:: html+jinja

    <title>
        {% if block('title') %}
            {{ block('title') }} | Starwars Events
        {% else %}
            Events from a Galaxy, far far away
        {% endif %}
    </title>

Refresh the events page to see it in action.

Twig Whitespace Control
~~~~~~~~~~~~~~~~~~~~~~~

But if you view the source, you'll see that we've got a lot of whitespace
around the ``title`` tag. That's probably ok, but let's fix it anyways. By
adding a dash to any Twig tag, all the whitespace on that side of the tag
is removed. The end result is a title tag, with no whitespace at all:

.. code-block:: html+jinja

    <title>
        {%- if block('title') -%}
            {{ block('title') }} | Starwars Events
        {%- else -%}
            Events from a Galaxy, far far away
        {%- endif -%}
    </title>

Wow! Congrats on finishing the first episode! You're well on your way with
Symfony, so keep going with `Episode 2`_ and start practicing on a project.

Seeya next time!

.. _`GlobalVariables`: http://api.symfony.com/2.2/Symfony/Bundle/FrameworkBundle/Templating/GlobalVariables.html
.. _`block function`: http://twig.sensiolabs.org/doc/functions/block.html
.. _`Episode 2`: http://knpuniversity.com/screencast/symfony2-ep2
