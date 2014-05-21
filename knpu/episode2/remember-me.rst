Remember Me Functionality
=========================

I want to leave you with just one more tip. We talked a bit about the remember
me functionality, but we didn't actually see how to use it. Activate the
feature by adding the ``remember_me`` entry to your firewall and giving it
a secret, random key:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        firewalls:
            secured_area:
                # ...
                remember_me:
                    key: The name of our cat is Edgar!

Now, just open the login template and add a field named ``_remember_me``:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Login/login.html.twig #}
    {# ... #}

    <form ...>
        <input type="checkbox" name="_remember_me" />
        Remember me
    </form>


This works a bit like the login itself - as long as we have a ``_remember_me``
field and its checked, everything else happens automatically.

Try it out. After logging in, we can see that a ``REMEMBERME`` cookie has been
set. Let's clear our session cookie to make sure it's working. When I refresh,
my session is gone but I'm still logged in. Nice!

Alright, that's it for now! I hope I'll see you in future Knp screencasts.
Also, be sure to checkout `KnpBundles.com`_ if you're curious about all
the open source bundles that you can bring into your app. Seeya next time!

.. _`KnpBundles.com`: http://knpbundles.com/
