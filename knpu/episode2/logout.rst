Logging Out
===========

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

Security Inside Twig: is_granted
--------------------------------

We already know logging out in Symfony is really easy. As long as the ``logout``
key is present under our firewall and we have a route to ``/logout``, we can
surf to ``/logout`` and it'll just work. Symfony takes care of the details behind
the scenes.

Now let's add a link! Open up the homepage template and add the logout link.
Like always use the Twig path function and pass it the name of the route:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    <a class="button" href="{{ path('event_new') }}">Create new event</a>

    <a class="link" href="{{ path('logout') }}">Logout</a>

    {# ... #}

It works of course, but we only want it to show up when a user has logged in.
To test for this, use the Twig ``is_granted`` function and pass it a special
``IS_AUTHENTICATED_REMEMBERED`` string:

.. code-block:: html+jinja

    {% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}
        <a class="link" href="{{ path('logout') }}">Logout</a>
    {% endif %}

Trust Levels: IS_AUTHENTICATED_ANONYMOUSLY, IS_AUTHENTICATED_REMEMBERED, IS_AUTHENTICATED_FULLY
-----------------------------------------------------------------------------------------------

You see, in addition to normal roles like ``ROLE_USER`` and ``ROLE_ADMIN``,
every user also gets one to three special roles:

* First, ``IS_AUTHENTICATED_ANONYMOUSLY`` is given to *all* users, even those
  that haven't really logged in. If you're wondering how a role that *everyone*
  has could possibly be useful, the answer is subtle, and has to do with
  :ref:`white-listing URLs that should be public<symfony-ep2-whitelisting-urls>`.

* Next, ``IS_AUTHENTICATED_REMEMBERED`` is given to all users who have actually
  logged in during this session or who have come back via a permanent "remember me"
  cookie. If you have this role, then you're definitely a real user, but you
  may not have had to put in your username or password recently.

* Finally, ``IS_AUTHENTICATED_FULLY`` is given only to users who have logged
  in during *this* session.

Looking back at the template, by checking ``IS_AUTHENTICATED_REMEMBERED``,
we're only showing the logout link to users who are logged in, either via
a remember me cookie or because they recently entered their password. Now
that we know this, we can get fancy and also add a login link for those anonymous
souls:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    {% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}
        <a class="link" href="{{ path('logout') }}">Logout {{ app.user.username }}</a>
    {% else %}
        <a class="link" href="{{ path('login') }}">Login</a>
    {% endif %}

Denying Access From a Controller: AccessDeniedException
-------------------------------------------------------

If you're still with us, let's see a few more things about roles, otherwise hit
rewind and we will see you in a minute. First, login as user again and surf
to ``/new``. Since we have the ``ROLE_USER`` role, we're allowed access.
In the ``access_control`` section of ``security.yml``, change the role for
this page to ``ROLE_ADMIN`` and refresh:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_ADMIN }
            # ...

This is the access denied page. It means that we *are* authenticated, but
don't have access. Of course, if this were on production, the page would look
a bit different. We'll learn how to customize error pages in the next screencast.

The ``access_control`` section of ``security.yml`` is the easiest way to control
access to your application, but also the least flexible. Remove the ``access_control``
entry:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            # - { path: ^/new, roles: ROLE_USER }
            # ...

In most applications, you'll probably also need to enforce more fine-grained
controls right inside your controllers. Find the ``newAction`` of the ``EventController``.
To check if the current user has a given role, we need to get the "security context",
which is a scary sounding object with one easy method on it: ``isGranted``.
Use it to ask if the user has the ``ROLE_ADMIN`` role::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function newAction()
    {
        $securityContext = $this->container->get('security.context');
        if (!$securityContext->isGranted('ROLE_ADMIN')) {
            // panic?
        }

        // ...
    }

If she doesn't, we need to throw a very special exception:
:symfonyclass:`Symfony\\Component\\Security\\Core\\Exception\\AccessDeniedException`.
Add a ``use`` statement for this class and then throw it inside the ``if``
block. If you add a message, only the developers will be able to see it::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Symfony\Component\Security\Core\Exception\AccessDeniedException;
    // ...

    public function newAction()
    {
        $securityContext = $this->container->get('security.context');
        if (!$securityContext->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only an admin can do this!!!!')
        }

        // ...
    }

Why is this exception class so special? First, if the current user isn't already
logged in, this causes them to be correctly redirected to the login page.
If the user is logged in, this will cause the access denied status code 403
page to be shown. As I mentioned earlier, we'll learn how to customize these
error pages a bit later.

Phew! Security is hard, but you're well on your way to becoming a security
master! Now let's learn about loading users from the database.

.. sidebar:: A few Tweaks before Continuing!

    This last part was just an example of security in a controller, but we
    won't use it going forward!

    Before you continue, remove (or comment out) the ``if`` statement we
    just added to ``newAction``::

        public function newAction()
        {
            /*
             * left as an example - but enforcing security in security.yml
            $securityContext = $this->container->get('security.context');
            if (!$securityContext->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedException('Only an admin can do this!!!!')
            }
            */

            // ...
        }

    Also uncomment out the ``access_control`` entry and make sure it once
    again uses ``ROLE_USER``.
    
    .. code-block:: yaml

        # app/config/security.yml
        security:
            # ...
            access_control:
                - { path: ^/new, roles: ROLE_USER }
                # ...

.. _`parent() function`: http://twig.sensiolabs.org/doc/functions/parent.html