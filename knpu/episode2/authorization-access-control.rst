Authorization with Access Control
=================================

Before we keep going with authentication and make it possible to login, let's
try out our first piece of authorization and start denying access!

Head back to ``security.yml``. The easiest way to deny access is via the
``access_control`` section. Let's use its regular expression coolness to
protect any URLs that start with "/new" or "/create".

Roles are given to a user when they login and if you're not logged in, you
don't have any. Here, we're saying that you at least need ``ROLE_USER`` to
access these URLs:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_USER }
            - { path: ^/create, roles: ROLE_USER }

Try it out! When we try to add an event, we're redirected to ``/my-login-url``.
Hey! I know that URL! That's what we put for the ``login_path`` config key.

So here's the magic that just happened behind the scenes:

#. We tried to go to ``/new``. Since our anonymous user doesn't have any roles,
   the access controls kicked us out;

#. The firewall saves the day. Instead of giving us an access denied screen,
   it decides to give us a chance to login. The ``form_login`` key in tells
   the firewall that we want to use a good old fashioned login form, and
   that the login form should live at ``/my-login-url``.

It's *our* job to actually create the login page. And since we haven't yet,
we see the big ugly 404 error.

More access_control options
---------------------------

The ``access_control`` has a few more tricks to it. Head over to the
`Security chapter of the book`_ and find the section on ``access_control``.
I want you to read this, but the most important thing to know is that only
*one* ``access_control`` entry is matched on a request. Symfony goes down
the list, finds the first match, and uses *only* it to check authorization.
I'll show you an example during the :ref:`last chapter <symfony-ep2-access_control-whitelist>`.

There's also other goodies, like different access controls based on the user's
IP address or depending on which hostname is being accessed. You can even
make it so that a user is redirected to ``https``.

.. _`Security chapter of the book`: http://symfony.com/doc/current/book/security.html#understanding-how-access-control-works
