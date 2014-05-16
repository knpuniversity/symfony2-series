Basic Security
==============

Let's get started by diving into security. Symfony's security component is
an incredibly powerful library. It can easily connect with other authentication
systems - like Facebook or LDAP - or load user information from anywhere,
be that a database or even via an API. But this power also means added complexity.
But if you can understand how each piece works, you can do amazing things.

Authentication, Authorization and the Deathstar
-----------------------------------------------

Security is divided into two parts: authentication and authorization. The
first part, **authentication, checks the user's credentials**. Its job isn't
actually  to restrict access, but instead to make sure that every user is
identified in some way.

Think of a building (or maybe the deathstar) where you check-in with security
when entering. Both Storm Troopers and visitors check-in and each is given
an access card which in Symfony is called a token. They use the token to access
other parts of this fully armed and operational battle station. At any time,
everyone inside has a token, but some grant access to more interesting command
rooms than others.

This leads into the second part of security: authorization. **Authorization
is like an electronic lock on every door**. Depending on your token, you may
have access to some rooms but not others. Authorization is what actually *denies*
a user access to something. At this point, we don't actually care *who* a
user is, just whether or not the security check-point gave them a token with
enough access to enter a specific room.

Security configuration: security.yml
------------------------------------

Let's see how this looks in code. The security configuration lives entirely
in the ``app/config/security.yml`` file. This file is included from the main
config file and is separated just to keep the somewhat long security configuration
by itself:

.. code-block:: yaml

    # app/config.config.yml
    imports:
        # ...
        - { resource: security.yml }

security.yml: Firewalls
~~~~~~~~~~~~~~~~~~~~~~~

The most important part of this file is the "firewalls" key. A firewall represents
the authentication layer. In other words, a firewall is the security check-point
for your app. Just like in a building, it makes sense to have just one security
entrance that everyone passes through. Let's change our configuration to
have just one firewall.

Remove the demo paths that are setup by default to that the login and logout
URLs are more traditional. The pattern is a regular expression that matches
against the URL being requested. The ``^/`` means that every request will
match this firewall and pass through our security checkpoint.

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        firewalls:
            # only 1 firewall is needed
            secured_area:
                pattern:    ^/
                # ...

Next, we'll add the ``anonymous`` key. This means that anonymous users are
allowed to come into the site, similar to letting a visitor enter the deathstar.
We may want to require login for certain pages, but we won't enforce that
here.

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        firewalls:
            secured_area:
                pattern:    ^/
                form_login:
                    check_path: /login_check
                    login_path: /login
                logout:
                    path:   /logout
                    target: /
                anonymous: ~

.. tip::

    You can also use route names (e.g. ``login_check``) instead of paths
    (e.g. ``/login_check``) once you have routes setup for each of these
    URIs.

With this, refresh the page. You'll see an "anon" string in the web debug
toolbar. Clicking it shows us that we're now "authenticated" in the system.
This may seem strange at first, but anonymous users are seen as "authenticated".
We'll see later how you can check to see if a user has actually logged in
or not.

Authorization with access_control
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Head back to the security file. Right now, we have a firewall that covers
our entire site, but visitors have access to everything. One easy way to enforce
authorization is via access controls. For example, let's use regular expressions
to protect "/new" and "/create" in our event app. Roles are what's given to
a user when they authenticate. In this case, we're saying that you at least
need ``ROLE_USER`` to access these pages:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_USER }
            - { path: ^/create , roles: ROLE_USER }

Try it out! When we try to add an event, we're redirected to "/login", which
doesn't actually exist yet. This is the magic of the firewall: two quick
things just happened behind the scenes:

1) We tried to go to "/new". Since our anonymous user doesn't have any roles,
   the access controls kick us out

2) The firewall saves the day. Instead of just giving us an access denied
   screen, it decides to give us a chance to login. The ``form_login`` key
   in ``security.yml`` tells the firewall that we want to use a good old fashioned
   login form, and that the login form should live at ``/login``.

Creating a Login Form
---------------------

So where's the actual login form? This is our job - the security layer just
helps us by redirecting the user.

Let's start by creating a new ``UserBundle`` that will house anything related
to security. I *could* use the ``app/console generate:bundle`` task, but instead
I'll show you how easy creating a bundle really is. Just create a directory
and an empty "Bundle" class with the right name. A bundle is nothing more
than a directory with a bundle class::

    // src/Yoda/UserBundle/UserBundle.php
    namespace Yoda\UserBundle;

    use Symfony\Component\HttpKernel\Bundle\Bundle;

    class UserBundle extends Bundle
    {
    }

When you're done, just activate it in the AppKernel class and, voila! A brand
new bundle::

    // app/AppKernel.php
    // ...

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Yoda\UserBundle\UserBundle(),
        );

        // ...
    }

To create the login page, let's create a ``LoginController`` class with a
``loginAction`` method. Remember, a controller is nothing more than a plain
old PHP class with a method for each action::

    // src/Yoda/UserBundle/Controller/LoginController.php
    namespace Yoda\UserBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    class LoginController extends Controller
    {
        public function loginAction()
        {
        }
    }

Using Annotation Routing
~~~~~~~~~~~~~~~~~~~~~~~~

If you watched part one of our series, you'll probably expect me to create
a ``routing.yml`` file inside the new bundle and import it. Instead, I'm going
to use `annotation routing`_. First, add the route annotation namespace::

    // src/Yoda/UserBundle/Controller/LoginController.php
    // ...

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

    class LoginController extends Controller
    {
        // ...
    }

Next, import the controller routes from the main routing file:

.. code-block:: yaml

    # app/config/routing.yml
    # ...

    user_routes:
        resource: "@UserBundle/Controller"
        type: annotation

Now, a route can be added right above the action::

    // src/Yoda/UserBundle/Controller/LoginController.php
    // ...

    /**
     * @Route("/login", name="login")
     */
    public function loginAction()
    {
        // ... todo still..
    }

If we try that page, we can see that our route is working.

The loginAction Logic
~~~~~~~~~~~~~~~~~~~~~

Most of the login action and template are pretty boilerplate, so let's copy
them from the docs. Head to the security chapter and find the `login form section`_.
Copy the login action and paste it into our controller. Don't forget to add
the ``use`` statement for the SecurityContext class that's referenced::

    // src/Yoda/UserBundle/Controller/LoginController.php
    namespace Yoda\UserBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Symfony\Component\Security\Core\SecurityContext;
    use Symfony\Component\HttpFoundation\Request;
    // ...

    class LoginController extends Controller
    {
        /**
         * @Route("/login", name="login")
         */
        public function loginAction(Request $request)
        {
            $session = $request->getSession();

            // get the login error if there is one
            if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
                $error = $request->attributes->get(
                    SecurityContext::AUTHENTICATION_ERROR
                );
            } else {
                $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
                $session->remove(SecurityContext::AUTHENTICATION_ERROR);
            }

            return $this->render(
                'AcmeSecurityBundle:Security:login.html.twig',
                array(
                    // last username entered by the user
                    'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                    'error'         => $error,
                )
            );
        }

.. _symfony-ep2-template-annotation:

The Template Annotation Shortcut
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In the pasted code, a template is rendered by calling the ``render`` method.
That's fine, but let's take advantage of another shortcut. Add the `Template annotation`_
to the class and place ``@Template`` above the method. Remove the ``render``
call and just return the array of data you want to pass into your template::

    // src/Yoda/UserBundle/Controller/LoginController.php
    // ...

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

    class LoginController extends Controller
    {
        /**
         * @Route("/login", name="login")
         * @Template()
         */
        public function loginAction()
        {
            // ...

            return array(
                // last username entered by the user
                'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                'error'         => $error,
            );
        }
    }

``@Template`` tells Symfony to render a template for us and pass in the data
we've returned. Both the ``@Route`` and ``@Template`` shortcuts are part of the
`SensioFrameworkExtraBundle`_, which has its own documentation at symfony.com.

Login Template
~~~~~~~~~~~~~~

Next, create the template file and copy in the code from the documentation.
Add the extends and block tags to fit it into our base layout. I'll add a bit
more markup to fit things into our site:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Login/login.html.twig #}
    {% extends '::base.html.twig' %}

    {% block body %}
    <section class="login">
        <article>

            {% if error %}
                <div class="error">{{ error.message }}</div>
            {% endif %}

            <form action="{{ path('login_check') }}" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="_username" value="{{ last_username }}" />

                <label for="password">Password:</label>
                <input type="password" id="password" name="_password" />

                <button type="submit">login</button>
            </form>

        </article>
    </section>
    {% endblock %}

Handling Login: login_check
~~~~~~~~~~~~~~~~~~~~~~~~~~~

The copied template submits to a route called ``login_check``. Let's create
another action right now that defines that route. Notice that I'm leaving
the action blank: if we were to hit it, we should get an error::

    // ...
    // src/Yoda/UserBundle/Controller/LoginController.php

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
    }

But we won't hit this route. Head back to your browser to witness one of the
strangest parts of Symfony's security system. When we login using "user" and
"userpass"... it works! We can see our username on the web debug toolbar and
even a role assigned to us. Let's find out how this works.

The login page is pretty plain and currently very ugly. But when we submit
to ``login_check``, Symfony's security system intercepts the request and processes
the login information. This works automatically as long as we POST ``_username``
and ``_password`` to the URL ``/login_check``. This URL is special because it's
configured as the ``check_path`` in the ``form_login`` section. The ``loginCheckAction``
method will *never* be executed, since Symfony intercepts requests to that
URL. When the login is successful, the user is redirected to the page they
last visited or the homepage. If the login fails, the user is sent back to
the login page.

For now, the users themselves are just being loaded directly from ``security.yml``.
For simple sites, this is great. But in the next chapter, we'll store our
users in the database instead:

.. code-block:: yaml

    # app/config/security.yml
    # ...
    providers:
        in_memory:
            memory:
                # this was here when we started: 2 hardcoded users
                users:
                    user:  { password: userpass, roles: [ 'ROLE_USER' ] }
                    admin: { password: adminpass, roles: [ 'ROLE_ADMIN' ] }

Logging Out
-----------

What about logging out? As you may have guessed, Symfony helps us with logging
out as well. The only requirement is that we have a route that matches the
logout ``path`` in our ``security.yml``. Let's get to it! Add another action
with a ``/logout`` route. Just like with the ``loginCheckAction``, the code
here won't actually get hit. Instead, Symfony will intercept the request and
process the logout::

    // ...
    // src/Yoda/UserBundle/Controller/LoginController.php

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
    }

Try it out by going to ``/logout`` manually. Great! As you can see by the
web debug toolbar, we're anonymous once again.

Cleaning up loginAction
-----------------------

Next, let's fail the login. Notice that we get the "Bad Credentials" error.
When we fail a login, the error is saved to the session. This is all visible in
``loginAction``. In fact, get rid of the if statement and just leave the
second part. The first part is not useful unless you reconfigure the login
system to forward you to the login page::

    // src/Yoda/UserBundle/Controller/LoginController.php
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

    {# src/Yoda/UserBundle/Resources/views/Login/login.html.twig #}

    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('bundles/user/css/login.css') }}" />
    {% endblock %}

Of course if we did this, we'd really have a broken site! Instead of replacing
the ``stylesheets`` block, we want to add to it. The trick is the
Twig `parent() function`_. By including this, all the parent block's content
is included first:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Login/login.html.twig #}

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

    {# src/Yoda/UserBundle/Resources/views/Login/login.html.twig #}

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

.. _`annotation routing`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/routing.html
.. _`login form section`: http://symfony.com/doc/current/book/security.html#using-a-traditional-login-form
.. _`Template annotation`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/view.html
.. _`SensioFrameworkExtraBundle`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html
.. _`parent() function`: http://twig.sensiolabs.org/doc/functions/parent.html