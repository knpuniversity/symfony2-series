Switching Users / Impersonation
===============================

Let's look at just a few more quick things. Notice the ``ROLE_ALLOWED_TO_SWITCH``
role in ``security.yml``. What's that all about? One feature of Symfony is
the ability to actually change which user you're logged in as. Ever have a
client complaint you couldn't replicate? Well now you can login as them without
knowing their password to test things.

To activate this feature, add the ``switch_user`` key to your firewall:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        firewalls:
            secured_area:
                # ...
                switch_user: ~

To use it, just add a ``_switch_user`` query parameter to any page with the
username you want to change to:

    http://events.local/app_dev.php/new?_switch_user=user

When we try it initially, we get the access denied screen. Our user needs
``ROLE_ALLOWED_TO_SWITCH`` to be able to do this. Add it to the ``ROLE_ADMIN``
hierarchy to get it:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        role_hierarchy:
            ROLE_ADMIN:       [ROLE_USER, ROLE_EVENT_MANAGER, ROLE_ALLOWED_TO_SWITCH]
            # ...

When we refresh, you'll see that the our username in the web debug toolbar
has changed to user. So cool! To switch back, use the ``_exit`` key:

.. code-block:: text

    http://events.local/app_dev.php/new?_switch_user=_exit
