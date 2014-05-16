Twig Security and IS_AUTHENTICATED_FULLY
========================================

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
