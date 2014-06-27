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
                    key: "Order 1138"

Next, open the login template and add a field named ``_remember_me``:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Login/login.html.twig #}
    {# ... #}

    <form ...>

        <hr/>
        Remember me <input type="checkbox" name="_remember_me" />
        <button type="submit" class="btn btn-primary pull-right">login</button>
    </form>

This works a bit like login does: as long as we have a ``_remember_me``
checkbox and it's checked, Symfony will take care of everything automatically.

Try it out! After logging in, we now have a ``REMEMBERME`` cookie. Let's
clear our session cookie to make sure it's working. When I refresh,
my session is gone but I'm still logged in. Nice! Click anywhere on the web
debug toolbar to get into the profiler. Next, click on the "Logs" tab. If
you look closely, you can even see some logs for the remember me login process:

.. code-block:: text

    DEBUG - Remember-me cookie detected.
    INFO - Remember-me cookie accepted.
    DEBUG - SecurityContext populated with remember-me token.

Ok gang, that's all for now! I hope I'll see you in future Knp screencasts.
And remember to check out `KnpBundles.com`_ if you're curious about all
the open source bundles that you can bring into your app. Seeya next time!

.. _`KnpBundles.com`: http://knpbundles.com/
