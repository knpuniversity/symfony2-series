Security Fundamentals
=====================

Symfony comes with a security component that's really powerful. Honestly,
it's also really complex. It can connect with other authentication systems
- like Facebook or LDAP - or load user information from anywhere, like a
database or even across an API.

The bummer is that hooking all this up can be tough. But since you'll know
how each piece works, you'll be able to do amazing things. There's also some
jedi magic I'll show you later that makes custom authentication systems much easier.

Authentication, Authorization and the Death Star
------------------------------------------------

Security is two parts: authentication and authorization.
**Authentication, checks the user's credentials**. Its job is *not* to restrict
access, it just wants to know *who* you are.

Ok, so think of a building, or maybe even the Death Star. After the tractor
beam forces you to land, you walk out and pass through a security checkpoint.
Both Stormtroopers *and* rebels check-in here, prove who they are and receive
an access card, or a *token* in Symfony-speak.

Proving who you are and getting a token: that's authentication.

The token can be used to unlock doors in this fully armed and operational
battle station. Everyone inside has a token, but some grant more access than
others. The second part of security, authorization, is like the lock that's
on every door. It actually *denies* a user access to something. Authorization
doesn't care if you're Obi-Wan or a Stormtooper, it only checks to see if
the token you received has enough access to enter a specific room.

Security configuration: security.yml
------------------------------------

Let's talk authentication first, which can be more complex than authorization.
The security configuration lives entirely in the ``app/config/security.yml``
file, which is imported from the main ``config.yml`` file:

.. code-block:: yaml

    # app/config.config.yml
    imports:
        # ...
        - { resource: security.yml }

Security config lives in its own file because, well, it's kind of big and
ugly. But there's no technical reason: you could move all of this into ``config.yml``
and it would work just the same.

Firewalls Configuration (security.yml)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. note::

    If your ``security.yml`` file is mostly empty, don't worry! You installed
    Symfony in a slightly different way. Just download the code for this
    tutorial and replace your ``security.yml`` file with the one from the
    download.

Find the ``firewalls`` key: it's the most important part in this file. A
firewall represents the authentication layer, or security check-point for
your app. Delete the ``login`` and ``dev`` firewall sections so that we have
just one firewall:

.. code-block:: yaml

    # app/config/security.yml
    # ...

    firewalls:
        secured_area:
            pattern:    ^/demo/secured/
            form_login:
                check_path: _security_check
                login_path: _demo_login
            logout:
                path:   _demo_logout
                target: _demo
            #anonymous: ~
            #http_basic:
            #    realm: "Secured Demo Area"

Just like in a giant floating death machine, it make sense for everyone to pass
through the same security system that looks up people in the same corrupt,
imperial database. In fact, change the ``pattern`` key to be ``^/``:

.. code-block:: yaml

    # app/config/security.yml
    # ...

    firewalls:
        secured_area:
            pattern:    ^/
            # ...

Now, every request that goes to our app will use this one firewall for authentication.
Let's also change the ``login_path`` key to be ``/my-login-url``:

.. code-block:: yaml

    # app/config/security.yml
    # ...

    firewalls:
        secured_area:
            pattern:    ^/
            form_login:
                check_path: _security_check
                login_path: /my-login-url
            # ...

Don't worry about what this or any of the other keys mean yet: they're just
there to confuse you. I'll explain it all in a second.

Anonymous Access (security.yml)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Now, uncomment the ``anonymous`` key:

.. code-block:: yaml

    # app/config/security.yml
    # ...

    security:
        # ...
        firewalls:
            secured_area:
                pattern:    ^/
                # ...
                anonymous: ~

This lets anonymous users into the site, similar to letting a tourist enter
the Death Star. We may want to require login for certain pages, or even maybe
nearly every page. But we're not going to do that here. Remember, the firewall
is all about finding out *who* you are, not denying access.

Head back to the browser, but don't refresh! First, notice the little red
icon on your web debug toolbar. When you hover over it, it says "You are
not authenticated". 

Now refresh. Yay! It's green and says "anon". Clicking it shows us that we're
now "authenticated". Yes, it's a bit odd, but anonymous users are actually
authenticated, since they passed through our firewall.

But don't panic, it's easy in code to check if the user has *actually* logged
in or not. I'll show you later. Of course, we haven't actually done the work
to make it possible to login yet, but we'll get to those silly details in
a second.

