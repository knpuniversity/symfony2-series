Filtering, Combining and other Craziness with Assetic
=====================================================

Life is simple, but things can get crazy with CSS and JS. If you use LESS
or SASS, you'll need to process those into CSS before seeing your changes.
On deploy, you'll probably also want to combine your CSS into a single file
and remove all the extra whitespace to speed up your user's experience.
There are also tools like RequireJS, really the list goes on and on.

Frontend Tools: Grunt
---------------------

These days, tools exist outside of PHP to help solve these problems. For
example, `Grunt`_ is a tool to help you build your assets, like processing
through SASS, minifiying and combining. If you're a frontend developer or
have one on your team and are comfortable using these tools, go for it. We
even have a blog post `Evolving RequireJS, Bower and Grunt`_ with code that
shows you an approach of using some of this with Symfony.

Assetic: For the Backend Guy
----------------------------

But if you're more of a backend dev and just want some help with minifying
and combining files, it's all good. Symfony uses a tool called Assetic which
makes this *almost* painless :).

Using the stylesheets Tag
-------------------------

Open up your base template and add a new ``stylesheets`` tag. This has the strangest
syntax, but should include the path to our 3 CSS files, a filter called ``cssrewrite``,
and an actual ``link`` tag. Remove the 3 hard-coded link tags we just added:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}
    
    {% block stylesheets %}
        {# link tag for bootstrap... #}

        {% stylesheets
            'bundles/event/css/event.css'
            'bundles/event/css/events.css'
            'bundles/event/css/main.css'
            filter='cssrewrite'
        %}
            <link rel="stylesheet" href="{{ asset_url }}" />
        {% endstylesheets %}
    {% endblock %}

Refresh the page. Ok, things still work. Now view the source.

.. code-block:: html

    <link rel="stylesheet" href="/css/8e49901_event_1.css" />
    <link rel="stylesheet" href="/css/8e49901_events_2.css" />
    <link rel="stylesheet" href="/css/8e49901_main_3.css" />

Hmm. So we still have 3 link tags, but the location has changed. What's even
stranger is that these 3 files don't exist - we don't even have a ``web/css``
directory.

When the browser requests these files, they actually hit our Symfony app
and are processed by an internal Assetic controller that renders the CSS
code. And I can even prove it!

Run the ``router:debug`` console task:

.. code-block:: bash

    php app/console router:debug

At the top, you'll see actual routes that match the CSS files:

    Name                        Path
    _assetic_8e49901_0          /css/8e49901_event_1.css           
    _assetic_8e49901_1          /css/8e49901_events_2.css          
    _assetic_8e49901_2          /css/8e49901_main_3.css  


These routes showed up automatically, just by adding the ``stylesheets``
tag. And if we change any of these CSS files and refresh, these routes will
return the updated file.

On the surface, nothing has changed. But the magic is coming...

The cssrewrite Filter
---------------------

Assetic exists for 2 reasons, and the first is to apply filters to your CSS
and JS. For example, Assetic has a ``less`` filter that processes your less
files into CSS before returning them.

If you look back at the ``stylesheets`` tag, you can see that we *do* have
one filter called ``cssrewrite``.

Open up the generated ``event_1.css`` file in your browser *and* the original
``event.css`` in your editor. Now, find the background image for ``pinpoint.png``
in each. Huh, the paths are a bit different!

.. code-block:: text

    The original event.css:
    
        background: url(../images/pinpoint.png) no-repeat -5px -7px;

    The event.css that's served in (generated for) the browser:

        background: url(../../bundles/event/images/pinpoint.png) no-repeat -5px -7px;

Why? In the browser's eyes, the file lives in ``/css``, but the original
lived in ``/bundles/event/css``. If the generated file used the original
url, it would point to ``/images/pinpoint.png`` instead of ``/bundles/event/images/pinpoint.png``.
The ``cssrewrite`` filter dynamically changes the url so that things still
work. Crazy, right?

This filter is less of a cool feature and more of a necessity. But Assetic
supports a number of `other filters`_. As a fair warning, a lot of them aren't
documented.

.. _`Grunt`: http://gruntjs.com/
.. _`Evolving RequireJS, Bower and Grunt`: http://knpuniversity.com/blog/requirejs-bower-grunt
.. _`other filters`: https://github.com/kriswallsmith/assetic#filters
