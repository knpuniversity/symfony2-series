Twig
====

Now for the absolutely hardest part of Symfony. I'm kidding! We're talking
about templates: those files where we mix HTML tags and dynamic data to build
our page. We already saw that these are rendered using the ``templating``
service, usually from inside a controller.

We also know that they're written in Twig, a language that feels a lot like
PHP, but was made specifically to be awesome at doing templating tasks, like
looping, rendering other templates, and handling layouts.

If you haven't seen Twig before, trust me, you're going to love it! It's
easy and a joy to work with.

.. tip::

    If you *do* end up hating Twig, you can use normal PHP templates as well,
    though 3rd party bundles don't support these as well.

The Print Syntax
----------------

Twig only has two different tags and we've already seen the first one: the
print tag:

.. code-block:: jinja

    {{ name }}

No matter what, if you want to print something in Twig, then you'll
use this double curly brace format. I call this the "say something" tag,
and it's basically the same as opening PHP and using ``echo``::

    <?php echo $name ?>

In our template, we're printing the ``name`` variable, which was passed to
the template from the controller.

The Do Something Sytnax
-----------------------

The second tag is the "do something" tag. Its syntax is ``{% %}`` and we
use it to do things like looping, defining variables, and if statements.
It's easier to see, so let's pass the count variable into our template::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    public function indexAction($count, $firstName)
    {
        return $this->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName, 'count' => $count)
        );
    }

Now, use Twig's ``for`` tag to print the name a certain number of times:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Default/index.html.twig #}

    {% for i in 1..count %}
        Hello <strong>{{ name }}</strong> # {{ i }}!<br/>
    {% endfor %}

Now refresh! There are a finite number of "do something" tags and there's
even a handy list on `Twig's Documentation page`_. Scroll down a little bit
and check out the list on the left.

.. tip::

    Symfony adds just a few more Twig "do something" tags. Find them in the
    `Symfony Reference`_ documentation.

So the ``{{ }}`` syntax prints things and the ``{% %}`` performs other language
actions. If you've got this down, I'd say you've just about mastered Twig.

Comments
--------

Ok, I hate to lie to you, so there *is* a third tag, but it's just used to
write comments.

.. code-block:: jinja

    {# Hello comments! #}

Filters
-------

But wait, there's more! Twig has a lot of nice tricks and sugar, like filters!
We can use the ``upper`` filter to capitalize the name variable:

.. code-block:: html+jinja

    Hello <strong>{{ name|upper }}</strong> # {{ i }}!<br/>

If you're used to piping things together in a UNIX terminal, this works the
same way. Back on the `Documentation page`_, you'll find a big list of filters,
You can even use filters on top of filters.

Twig also has functions and a cool thing called tests, which lets you write
things like ``{% if i is odd %}``. But that's all just extra fun stuff.

.. note::

    If you want to get deeper with these types of tricks or want to help
    your frontend designer get started, check out our `Twig Screencast`_.

Extending a Base Layout
-----------------------

Despite all my Twig hype, our template is depressing: it's got some HTML,
but no layout. If only we had a base layout template that could decorate
all of our page.

Oh right, there *is* one, and it lives in the ``app/Resources/views`` directory.
Actually, it's kind of plain too, but has a basic HTML structure:

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

To use this layout, we "extend" it. First, add the ``extends`` tag to the
top of the ``index.html.twig`` template. Now, wrap everthing else in a
``{% block body %}`` tag:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Default/index.html.twig #}
    {% extends '::base.html.twig' %}

    {% block body %}
        {# ... the rest of the template ... #}
    {% endblock %}

Refresh and check out the source. The HTML from ``base.html.twig`` is being
used and the content from our template is rendered in the middle of it.

Twig Blocks
-----------

Let's break this down. The ``extends`` tag says that we want to *dress* our
template with another template. Inside ``base.html.twig``, we have a bunch
of ``block`` tags. One of them is called ``body`` and looks just like what
we added to *our* template.

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

Blocks define "holes" that a child template can fill in. The content in the
``body`` block of ``index.html.twig`` is inserted into the ``body`` block
of ``base.html.twig``.

There's also a ``title`` block, which already has content in it:

.. code-block:: html+jinja

    <title>{% block title %}Welcome!{% endblock %}</title>

This block has default content, which is working since the page's title is
indeed ``Welcome!``.

Let's replace it with something a bit less boring. We know how to do this
now, just add a ``title`` block to ``index.html.twig``.

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Default/index.html.twig #}
    {% extends '::base.html.twig' %}

    {% block title %}Some Twiggy Goodness{% endblock %}

    {% block body %}
        {# ... #}
    {% endblock %}

And to be even lazier, there's a shorter syntax for simple blocks like this:

    {% block title 'Some Twiggy Goodness' %}

The blocks ones in ``base.html.twig`` are just suggestions, feel free to
change their names or add some more to have an even more flexible layout.

.. tip::

    Yes, you can also append to a block instead of replacing it. This is
    done with `parent()`_ and we chat about it in `Episode 2`_.

::base.html.twig Naming
-----------------------

The ``::base.html.twig`` filename looks weird. But it's actualy the exact
same syntax we're using in our controller, just in disguise!

Remember that a template name always has three parts:

* the bundle name
* a subdirectory
* and the template filename

In this case, the bundle name and subdirectory are just missing. When a template
name *has* the bundle part, it means the template lives in the ``Resources/views``
directory of that bundle. But when this part is missing, like here, it means
the template lives in the ``*app*/Resources/views`` directory. And since
the second part is missing too, it means it lives directly there, and not
in a subdirectory.

.. sidebar:: Template name and path examples

    * ``EventBundle:Default:index.html.twig``

        src/Yoda/EventBundle/Resources/views/Default/index.html.twig

    * ``EventBundle::index.html.twig``

        src/Yoda/EventBundle/Resources/views/index.html.twig

    * ``::base.html.twig``

            app/Resources/views/index.html.twig

Web Debug Toolbar
-----------------

In the browser, we're now staring at a killer feature of Symfony2: the
polite little bar on the bottom. This is the web debug toolbar, and you may
end up loving it even more than the console.

It tells us which controller was rendered, the page load time, memory footprint,
security info, form details and more. It's added automatically to any page
that has a valid HTML structure. That's why we didn't see it until we extended
the layout file.

Click anywhere on it to multiply the amount of information it gives you by
100! This is the profiler, which is broken down into sections. The best one
is the Timeline. It visually tells us *exactly* what's going on during a
request and how much time everything is taking. A lot of what you see here
are background Symfony events.

.. _Twig: http://twig.sensiolabs.org/doc/tags/index.html#tags
.. _`Twig's Documentation page`: http://twig.sensiolabs.org/documentation
.. _`Documentation page`: http://twig.sensiolabs.org/documentation
.. _`Twig Screencast`: http://knpuniversity.com/screencast/twig
.. _`parent()`: http://twig.sensiolabs.org/doc/functions/parent.html
.. _`Episode 2`: http://knpuniversity.com/screencast/symfony2-ep2/basic-security#adding-css-to-a-single-page
.. _`Symfony Reference`: http://symfony.com/doc/current/reference/twig_reference.html
