Logging Out and Cleaning Up
===========================

What about logging out? Symfony has some magic for that too!

Look at the ``logout`` part of ``security.yml``:

.. code-block:: yaml

    # app/config/security.yml
    # ...
    firewalls:
        secured_area:
            # ...
            logout:
                path:   _demo_logout
                target: _demo

``path`` is the name of your logout route. Set it to ``logout`` - we'll create
that route in a second. ``target`` is where you want to redirect the user
after logging out. We already have a route called ``event``, which is our
event list page. Use that for ``target``:

.. code-block:: yaml

    # app/config/security.yml
    # ...
    firewalls:
        secured_area:
            # ...
            logout:
                path:   logout # a route called logout
                target: event  # a route called event

To make the ``logout`` route, let's add another method inside ``SecurityController``
and use the ``@Route`` annotation::

    // ...
    // src/Yoda/UserBundle/Controller/SecurityController.php

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
    }

Just like with the ``loginCheckAction``, the code here won't actually get
hit. Instead, Symfony intercepts the request and processes the logout for us.

Try it out by going to ``/logout`` manually. Great! As you can see by the
web debug toolbar, we're anonymous once again.

Cleaning up loginAction
-----------------------

If we fail login, we see a "Bad Credentials" message. When Symfony handles
the login, it saves this error to the session under a special key, and we're
just fetching it out in ``loginAction``.

Actually, we have more code than we need here. Remove the if statement and
just leave the second part::

    // src/Yoda/UserBundle/Controller/SecurityController.php
    // ...

    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        // get the login error if there is one
        $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);
        $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);

        return array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContextInterface::LAST_USERNAME),
            'error'         => $error,
        );
    }

The first part isn't used unless you reconfigure how Symfony sends you to
the login page.

.. note::

    The configuration I'm talking about here is the ``use_forward``, which
    causes Symfony to forward to the login page, instead of redirecting.

Adding CSS to a Single Page
---------------------------

I know I know, the login page is embarrassing looking. So I made a ``login.css``
file to fix things - find it in the ``resources/episode2`` directory of the
code download.

Let's move it into a ``Resources/public/css`` directory in the ``UserBundle``.

.. code-block:: css

    /* src/Yoda/UserBundle/Resources/public/css/login.css */
    .login {
        width: 500px;
        margin: 100px auto;
    }

    /* for the rest of login.css, see the code download */

Just like in episode 1, run ``app/console assets:install`` and add the ``--symlink``
option, unless you're on Windows:

.. code-block:: bash

    php app/console assets:install --symlink

This creates a symbolic link from ``web/bundles/user`` to the ``Resources/public``
directory in UserBundle. Since ``web/`` is our application's document root,
this makes our new CSS file accessible in a browser by going to
``/bundles/user/css/login.css``.

So how can we add this CSS file to *only* this page? First, open up the base
template. Here, we have a bunch of blocks, including one called ``stylesheets``.
All of our global CSS link tags live inside of it:

.. code-block:: html+jinja

    # app/Resources/views/base.html.twig
    # ...

    {% block stylesheets %}
        {% stylesheets
            'bundles/event/css/event.css'
            'bundles/event/css/events.css'
            'bundles/event/css/main.css'
            filter='cssrewrite'
        %}
            <link rel="stylesheet" href="{{ asset_url }}" />
        {% endstylesheets %}
    {% endblock %}

Let's override this block in ``login.html.twig`` and add the new link tag
to ``login.css``:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}

    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('bundles/user/css/login.css') }}" />
    {% endblock %}

Cool, but do you see the problem? This would entirely *replace* the block,
but we want to *add* to it. The trick is the Twig `parent() function`_. By
including this, all the parent block's content is included first:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}

    {% block stylesheets %}
        {{ parent() }}

        <link rel="stylesheet" href="{{ asset('bundles/user/css/login.css') }}" />
    {% endblock %}

Refresh now. Much less embarrassing looking. When you need to add CSS or
JS to just one page, this is how you do it.

And by adding a little error class, it looks even better:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}
    {# ... #}

    {% if error %}
        <div class="error">{{ error.message }}</div>
    {% endif %}

And while we're making things look better, let's open up ``base.html.twig``
and add a link tag to the Bootstrap CSS file. Just use a CDN URL for simplicity:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}

    {% block stylesheets %}
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css"/>
        
        ...
    {% block stylesheets %}

Back in ``login.html.twig``, I'll tweak the submit button so things look
nicer:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}
    {# ... #}
    
    <hr/>
    <button type="submit" class="btn btn-primary pull-right">login</button>

Refresh! Ah, much better. I'm a programmer, but I don't want the site to
look totally embarrassing!

.. _symfony-ep2-login-error-translation:

Translating the Login Error Message
-----------------------------------

While we're here, let's change that "Bad Credentials" message, it's a little,
"programmery". The message comes from deep inside Symfony. So to customize
it, we'll use the translator.

First, use the Twig ``trans`` filter on the message:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}
    {# ... #}

    {% if error %}
        <div class="error">{{ error.message|trans }}</div>
    {% endif %}

Next, create a translation file in ``app/Resources/translations/messages.en.yml``.
This file is just a simple key-value pair of translations:

.. code-block:: yaml

    # app/Resources/translations/messages.en.yml
    "Bad credentials": "Wrong password bro!"

Now, we just need to activate the translation engine in ``app/config.yml``:

.. code-block:: yaml

    framework:
        # ...
        translator:      { fallback: %locale% }

Ok now, try it! Again, so much better!

.. _`parent() function`: http://twig.sensiolabs.org/doc/functions/parent.html
