Twig Security and IS_AUTHENTICATED_FULLY
========================================

Since logging out works, let's add a link to actually do it.

We already know logging out in Symfony is really easy. As long as the ``logout``
key is present under our firewall and we have a route to ``/logout``, we can
surf there and it'll just work. Symfony takes care of the details behind
the scenes.

Security Inside Twig: is_granted
--------------------------------

Open up the homepage template and add the logout link. This is just like
generating any other URL: use the Twig ``path`` function and pass it the
name of the route:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    <a class="button" href="{{ path('event_new') }}">Create new event</a>

    <a class="link" href="{{ path('logout') }}">Logout</a>

    {# ... #}

It works of course, but we don't want to show it unless the user is *actually*
logged in. To test for this, use the Twig ``is_granted`` function and pass
it a special ``IS_AUTHENTICATED_REMEMBERED`` string:

.. code-block:: html+jinja

    {% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}
        <a class="link" href="{{ path('logout') }}">Logout</a>
    {% endif %}

And that works perfectly!

Trust Levels: IS_AUTHENTICATED_ANONYMOUSLY, IS_AUTHENTICATED_REMEMBERED, IS_AUTHENTICATED_FULLY
-----------------------------------------------------------------------------------------------

``is_granted`` is how you check security in Twig, and we also could have
passed normal roles here like ``ROLE_USER`` and ``ROLE_ADMIN``, instead of
this ``IS_AUTHENTICATED_REMEMBERED`` thingy. So in addition to checking to
see if the user has a given role, Symfony has 3 other special security checks
you can use.

* First, ``IS_AUTHENTICATED_REMEMBERED`` is given to all users who are logged
  in. They may have actually logged in during the session *or* may be logged
  in because they have a "remember me" cookie.

* Second, ``IS_AUTHENTICATED_FULLY`` is actually stronger. You only have
  this if you've *actually* logged in during this session. If you're logged
  in because of a remember me cookie, you won't have this;

* Finally, ``IS_AUTHENTICATED_ANONYMOUSLY`` is given to *all* users, even
  if you're not logged in. And since literally *everyone* has this, it seems
  worthless But it actually *does* have a use if you need to 
  :ref:`white-list URLs that should be public<symfony-ep2-whitelisting-urls>`.
  I'll show you an example in the last chapter.

Since we're checking for ``IS_AUTHENTICATED_REMEMBERED``, we're showing the
logout link to anyone who is logged in, via a remember me cookie or because
they recently entered their password. We want to let both types of users
logout.

Let's get super fancy and add a login link for those anonymous souls:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    {% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}
        <a class="link" href="{{ path('logout') }}">Logout</a>
    {% else %}
        <a class="link" href="{{ path('login_form') }}">Login</a>
    {% endif %}

You'll probably want to use ``IS_AUTHENTICATED_REMEMBERED`` almost everywhere
and save ``IS_AUTHENTICATED_FULLY`` for pages that need to be really secure,
like checkout. If the user is *only* ``IS_AUTHENTICATED_REMEMBERED`` and
hits one of those pages, they'll be redirected to login.
