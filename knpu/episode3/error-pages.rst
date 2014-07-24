Customizing Error Pages and How Errors are Handled
==================================================

Sometimes things fall apart. And when they do, we show our users an error
page. Hopefully, a hilarious error page.

Right now, our 404 page isn't very hilarous, except for the little Pacman
ghost that's screaming "Exception detected". He's adorable.

We see this big descriptive error page because we're in the ``dev`` environment,
and Symfony wants to help us fix our mistake. In real life, also known as
the ``prod`` environment, it's different.

The Real Life: prod Environment
-------------------------------

To see our app in its "real life" form, put an ``app.php`` after ``localhost``:

    http://localhost:9000/app.php

We talked about environments and this ``app.php`` stuff in `episode 1`_.
If you don't remember it, go back and check it out!

The page *might* work or it might be broken. That's because we always need
to clear our Symfony cache when going into the ``prod`` environment:

.. code-block:: bash

    php app/console cache:clear --env=prod

Ok, now the site works. Invent a URL to see the 404 page. Ah gross! This
error page isn't hilarous at all! So where is the content for this page actually
coming from and how can we make a better experience for our users?

.. _symfony2-ep3-error-template:

Overriding the Error Template Content
-------------------------------------

To find out, let's just search the project! In PHPStorm, I can navigate
to ``vendor/symfony/symfony``, right click, then select "Find in Path". Let's
look for the "An Error Occurred text".

Ah hah! It points us straight to a file in the core Twig bundle called ``error.html.twig``.
Let's open that up!

.. tip::

    The location of the file is:
    
        vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Resources/views/Exception/error.html.twig

Cool, so how can we replace this with a template that has unicorns, or pirates
or anything better than this?

There's actually a *really* neat trick that let's you override *any* template
from *any* bundle. All we need to do is create a template with the same
name as this in *just* the right location.

This template lives in ``TwigBundle`` and in an ``Exception`` directory.
Create an ``app/Resources/TwigBundle/views/Exception/error.html.twig`` file.
Notice how similar the paths are - it's the magic way to override *any*
template from *any* bundle.

.. tip::

    ``app/Resources/AnyBundle/views/SomeDir/myTemplate.html.twig``
    will always override
    ``@AnyBundle/Resources/views/SomeDir/myTemplate.html.twig``

Now just extend the base template and put something awesome inside. I'm
going to abuse my ``login.css`` file to get this to look ok. I know, I really
need to clean up my CSS:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error.html.twig #}
    {% extends '::base.html.twig' %}

    {% block stylesheets %}
        {{ parent() }}
        <link rel="stylesheet" href="{{ asset('bundles/user/css/login.css') }}" />
    {% endblock %}

    {% block body %}
        <section class="login">
            <article>
                <h1>Ah crap!</h1>

                <div>These are not the droids you're looking for...</div>
            </article>
        </section>
    {% endblock %}

Refresh! Hey, don't act so surprised to see the same ugly template. We're in
the ``prod`` environment, we need to clear our cache after every change:

.. code-block:: bash

    php app/console cache:clear --env=prod

Refresh again. It's beautiful. The pain with customizing error templates
is that you need to be in the ``prod`` environment to see them. And that
means you need to remember to clear cache after every change.

Customizing Error Pages by Type (Status Code)
---------------------------------------------

But we have a problem: this template is used for *all* errors: 404 errors,
500 errors and even the dreaded `418 error`_!

I think we should at least have one template for 404 errors and another for
everything else. Copy the existing template and paste it into a new file
called ``error404.html.twig``. That's the trick, and this works for customizing
the error page of any HTTP status code.

We should keep the generic error template, but let's give it a different
message:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error.html.twig #}

    {# ... #}
    <h1>Ah crap!</h1>

    <div>The servers are on fire! Grab a bucket! Send halp!</div>

To see the 404 template, clear your cache and refresh again on an imaginary
URL. To see the other template, temporarily throw an exception in ``EventController::indexAction``
to cause a 500 error::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    public function indexAction()
    {
        throw new \Exception('Ahhhhahahhhah');
        // ...
    }

Head to the homepage - but with the ``app.php`` still in the URL. You should
see that the servers are in fact on fire, which I guess is cool. Remove this
exception before moving on.

Going Deeper with Exception Handling
------------------------------------

Behind the scenes, Symfony dispatches an event whenever an exception happens.
We haven't talked about events yet, but this basically means that if you
want, you can be nofitied whenever an exception is thrown anywhere in your
code. Why would you do this? You might want to do some extra logging or even
completely replace which template is rendered when an error happens.

We won't cover event listeners in this screencast, but there's a cookbook
called `How to Create an Event Listener`_ that covers it.

Normally, when there's an exception, Symfony calls an internal controller
that renders the error template. This class lives in Twigbundle and is called
``ExceptionController``. Let's open it up!

    The class lives at:
    vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Controller/ExceptionController.php

The guts of this class aren't too important, but you *can* see it trying
to figure out which template to render in ``findTemplate``. You can even
see it looking for the status-code version of the template, like ``error404.html.twig``::

    // vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Controller/ExceptionController.php
    // ...

    $template = new TemplateReference('TwigBundle', 'Exception', $name.$code, $format, 'twig');
    if ($this->templateExists($template)) {
        return $template;
    }

I'm making you stare at this class because, if you want, you can actually
override this entire controller. If you do that, then *your* controller function
will be called whenever there's an error and *you* can render whatever page
you want. That process is a bit more involved, but use it if you need to go
even further.

.. _`episode 1`: http://knpuniversity.com/screencast/symfony2-ep1/vhost#the-dev-and-prod-environments
.. _`418 error`: http://sitesdoneright.com/blog/2013/03/what-is-418-im-a-teapot-status-code-error
.. _`How to Create an Event Listener`: http://symfony.com/doc/current/cookbook/service_container/event_listener.html
