Logging Out and Cleaning Up
===========================

What about logging out? Symfony has some magic for that too!

Check out the ``logout`` part of ``security.yml``:

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
hit. Instead, Symfony intercepts the request and process the logout for us.

Try it out by going to ``/logout`` manually. Great! As you can see by the
web debug toolbar, we're anonymous once again.

Cleaning up loginAction
-----------------------

Next, let's fail the login. Notice that we get the "Bad Credentials" error.
When we fail a login, the error is saved to the session. This is all visible in
``loginAction``. In fact, get rid of the if statement and just leave the
second part. The first part is not useful unless you reconfigure the login
system to forward you to the login page::

    // src/Yoda/UserBundle/Controller/SecurityController.php
    // ...

    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        // get the login error if there is one
        $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
        $session->remove(SecurityContext::AUTHENTICATION_ERROR);

        return array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        );
    }

Adding CSS to a Single Page
---------------------------

Our page is pretty ugly, so let's add some CSS! We already have a special
``login.css`` prepared for *just* this page. Since it should live in the ``UserBundle``,
create a ``Resources/public/css`` directory and place it there.

.. code-block:: css

    /* src/Yoda/UserBundle/Resources/public/css/login.css */
    .login {
        width: 500px;
        margin: 100px auto;
    }

    /* for the rest of login.css, see the code download */

Like in episode 1, we can run the ``assets:install`` command after creating
a ``Resources/public`` directory in a bundle. This creates a symbolic link
so that the new CSS file is accessible via ``bundles/user/css/login.css``
with respect to the public directory:

.. code-block:: bash

    php app/console assets:install --symlink

To include the file in *just* this template, let's use some Twig magic! Recall
that our base layout has several blocks. One of them is ``stylesheets``, and
it brings in all of our base CSS. We can easily override this block in our
template by redefining it and adding in a ``link`` tag for ``login.css``:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}

    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('bundles/user/css/login.css') }}" />
    {% endblock %}

Of course if we did this, we'd really have a broken site! Instead of replacing
the ``stylesheets`` block, we want to add to it. The trick is the
Twig `parent() function`_. By including this, all the parent block's content
is included first:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}

    {% block stylesheets %}
        {{ parent() }}

        <link rel="stylesheet" href="{{ asset('bundles/user/css/login.css') }}" />
    {% endblock %}

This is the standard way of including page-specific CSS or JS files. Now the 
login form looks good. And by adding a little error class, it looks even better.

.. _symfony-ep2-login-error-translation:

Translating the Login Error Message
-----------------------------------

While we're here, let's do one more thing. The error "Bad Credentials" comes
from deep inside Symfony. The easiest way to customize it is by translating
it, which is really quite easy. First, add the ``trans`` filter to the string:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}

    {# ... #}
    {% block body %}
        {# ... #}

        {% if error %}
            <div class="error">{{ error.message|trans }}</div>
        {% endif %}

        {# ... #}
    {% endblock %}

Next, create an english translation file in ``app/Resources/translations/messages.en.yml``.
The translation is just a simple key-value pair:

.. code-block:: yaml

    # app/Resources/translations/messages.en.yml

    "Bad credentials": "Wrong password bro!"

Finally, turn the translation engine on in `app/config.yml`:

.. code-block:: yaml

    framework:
        # ...
        translator:      { fallback: %locale% }

Now, try it! So, much better!

.. _`parent() function`: http://twig.sensiolabs.org/doc/functions/parent.html