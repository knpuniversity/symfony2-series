Twig
====

Now for the lighter side of Symfony - templates! A template is a file where
you mix your HTML tags and dynamic data together to get a finished piece
of content. Template files are rendered with the templating engine, and are
most commonly used in a controller to generate the HTML content for a page.

By default, the template files in Symfony aren't written in PHP - they're
written in a special language called Twig. Twig is a lot like PHP, but was
designed to be easy to use and really good at common templating tasks, like
looping, rendering other templates, and handling layouts. It also has a lot
of useful shortcuts.

If you haven't seen Twig before, don't worry! It's really simple and a
joy to work with. And if you *do* end up hating it, normal PHP templates
are also supported by Symfony.

Twig only has two different tags to worry about and we've already seen the
first one: the print tag. In all cases, if you're printing something in Twig, 
then you'll use this double curly brace format. I call this the "say something"
tag, and it's basically equivalent to writing open PHP echo in PHP code:

.. code-block:: jinja

    {{ name }}

In our template, we're printing the ``name`` variable, which was passed to
the template when we rendered it.

The second tag is the "do something" tag, which is used to do things like
looping, defining variables, and rendering other templates. Let's see this
in action! First, let's pass the count variable into our template::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    public function indexAction($count, $firstName)
    {
        return $this->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName, 'count' => $count)
        );
    }

Next, use TWIG's ``for`` tag to print the name a certain number of times:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Default/index.html.twig #}

    {% for i in 1..count %}
        Hello <strong>{{ name }}</strong> # {{ i }}!<br/>
    {% endfor %}

Now refresh! There are a finite number of "do something" tags, and they're
covered in the `Twig`_ and `Symfony`_ documentation.

If you keep these two tags straight, then you've just about mastered Twig.
There *is* a third tag, but it's just used to write comments.

.. code-block:: jinja

    {# Hello comments! #}

Twig is full of lots of other little tricks. One is the filter system. For
example, we can use the ``upper`` filter to capitalize the name variable. If
you're used to piping things together in a UNIX terminal, this works the
same way:

.. code-block:: html+jinja

    Hello <strong>{{ name|upper }}</strong> # {{ i }}!<br/>

For a full run-down on what Twig can do, check out the Twig documentation.
The `Twig for Template Designers`_ page shows you all of Twig's tricks.

Extending a Base Layout
-----------------------

So far, our Twig template is a little sad: it's got some text, but no HTML
layout. What we need is a base layout file that can decorate all of our pages.
Actually, one already exists in the ``app/Resources/views`` directory. It's
bare-bones, but has a basic HTML structure.

To use this layout, we "extend" it. First, add the ``extends`` tag to the top
of the ``index.html.twig`` template. Now, wrap the rest of the template inside
a ``block`` ``body`` tag:

.. code-block:: html+jinja

    {% extends '::base.html.twig' %}

    {# ... the rest of the template ... #}

When we refresh and view source, you'll see that the HTML from the ``base.html.twig``
layout file is being used and that the content from our template is rendered
in the middle of it.

Let's break down what's happening. The ``extends`` tag says that we want to
*dress* our template with another template. The ``::base.html.twig`` template
name probably looks weird, but it shouldn't. This is the exact same syntax
we used in our controller to render the ``index.html.twig`` template. Remember
that a template name always has three parts: the bundle name, a subdirectory,
and the template filename. In this case, the bundle name and subdirectory
are missing. When a template name has a bundle, it means that the template
lives in the ``Resources/views`` directory of that bundle. But when the bundle
is missing - like here - it means that it lives in the ``*app*/Resources/views``
directory. The fact that the second part of the string is missing too just
means that the file lives directly in ``app/Resources/views`` and not in a
subdirectory.

.. sidebar:: Template name and path examples

    * ``EventBundle:Default:index.html.twig``

        src/Yoda/EventBundle/Resources/views/Default/index.html.twig

    * ``EventBundle::index.html.twig``

        src/Yoda/EventBundle/Resources/views/index.html.twig
        
    * ``::base.html.twig``

            app/Resources/views/index.html.twig

Twig Blocks
-----------

Inside the layout, you'll see several ``block`` tags that look like the one
we used in ``index.html.twig``:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="UTF-8" />
            <title>{% block title %}Welcome!{% endblock %}</title>
            {% block stylesheets %}{% endblock %}
            <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
        </head>
        <body>
            {% block body %}{% endblock %}
            {% block javascripts %}{% endblock %}
        </body>
    </html>

In this case, the blocks define "holes" that the child template can fill in.
The content in the ``body`` block of ``index.html.twig`` is inserted into
the ``body`` block of ``base.html.twig``.

You'll also notice a ``title`` block, but this time it has content in it.
This is another feature of blocks - a block can have default content. As you
would expect, the title of our page is "Welcome!" To replace this title add
a ``title`` block to ``html.index.twig``. Or, for simple blocks like this,
you can also use a shorter syntax:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Default/index.html.twig #}
    {# ... #}

    {% block title 'Some Twiggy Goodness' %}    

    {# ... #}

We'll use blocks all the time in our project to make really flexible layouts.
we'll keep playing with blocks to show off more of their tricks.

Web Debug Toolbar
-----------------

Before we move on, you may have noticed that a bar showed up at the bottom
of the page after we added the base layout. This is called the web debug
toolbar and it's one of the most awesome features. It has information about which
controller was rendered, the page load time, memory footprint, security and
more. The web debug toolbar is automatically added to any page that has a
valid HTML structure, which is why it didn't show up until we extended the
layout file.

If you click the little hash link, you'll be taken to the "profiler", which
has a lot more details, including the timeline feature. Use this to figure
out bottlenecks in performance and to see all the behind-the-scenes events
that happens with Symfony. We'll talk about events in an upcoming screencast.

.. _Twig: http://twig.sensiolabs.org/doc/tags/index.html#tags
.. _Symfony: http://symfony.com/doc/current/reference/twig_reference.html
.. _`Twig for Template Designers`: http://twig.sensiolabs.org/doc/templates.html