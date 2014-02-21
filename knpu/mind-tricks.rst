Twig Mind Tricks
================

The next two cool things involve Twig. In every Twig template in Symfony,
you have access to a variable called ``app``. This variable has a bunch of
useful things attached to it, like the request, the security context, the
User object, and the session:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    {% block body %}
        {# some examples - remove these after you try them #}
        {{ app.session.get('some_session_key') }}
        {{ app.request.host }}

        {# ... #}
    {% endblock %}

Actually, it's an object called `GlobalVariables`_, which you can check out
yourself. So when you need one of these things, remember that app variable!

.. tip::

    You can remove this code after trying it out - it's just an example of
    how you can access the request and session data - it doesn't add anything
    real to our project.

The block Twig Function
-----------------------

For cool thing #4, head to our base template. Right now, the title tag is
pretty boring: I can either replace it entirely in a child template or
not at all:

.. code-block:: html+jinja

    <title>{% block title %}Welcome!{% endblock %}</title>

Let's make this better by adding a little suffix whenever the page title is
overridden. This shows off the `block function`_, which lets us get at the
current value of a block:

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

But when you view the source, you'll see that we've got a lot of whitespace
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

That's it for now! I hope I'll see you in future Knp screencasts. Also, be
sure to checkout KnpBundles.com if you're curious about all the open source
bundles that you can bring into your app.

Seeya next time!

.. _`GlobalVariables`: http://api.symfony.com/2.2/Symfony/Bundle/FrameworkBundle/Templating/GlobalVariables.html
.. _`block function`: http://twig.sensiolabs.org/doc/functions/block.html
