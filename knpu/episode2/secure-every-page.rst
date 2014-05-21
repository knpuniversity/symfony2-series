.. _symfony-ep2-access_control-whitelist:

Whitelisting: Securing all Pages, except a few
==============================================

Next, look again at ``access_control``. Right now, our entire site is open
to the public, except for the specific pages that we're locking down:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create, roles: ROLE_EVENT_MANAGER }

This is a blacklisting strategy. If the majority of our site required login,
we could reverse. Add a new access control that matches *all* requests and
requires ``ROLE_USER``:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create, roles: ROLE_EVENT_MANAGER }
            - { path: ^/, roles: ROLE_USER }

Now, every page is locked down. Logout and try it. Mmm a redirect loop!

.. _symfony-ep2-whitelisting-urls:

We've got too much security. When we go to any page, we don't have access
and are redirected to ``/login``. Of course, we don't have access to ``/login``
either, so we're redirected to ``/login``. Do you see the problem?

To fix this, add a new ``access_control`` entry for any page starting with
``/login``. For the role, type ``IS_AUTHENTICATED_ANONYMOUSLY``:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create, roles: ROLE_EVENT_MANAGER }
            - { path: ^/, roles: ROLE_USER }

Refresh again. It works! We're missing our styles, but we'll fix that next.
The ``access_control`` entries match from top to bottom and stop after the
first match. When we go to ``/login``, the first control is matched and executed.
By saying ``IS_AUTHENTICATED_ANONYMOUSLY``, we're "whitelisting" this URL
pattern as one that should be available to everyone.

Run the ``router:debug`` task to see a few other URLs that we should whitelist,
including some URLs that load our CSS files as well as the web debug toolbar
and profiler during development:

    _assetic_01e9169                       ANY      /css/01e9169.css
    ...
    _wdt                                   ANY      /_wdt/{token}
    _profiler_home                         ANY      /_profiler/
    ... 

We haven't talked about assetic much yet, but by blocking it's URLs, we're
blocking our stylesheets. With these entries in place, we're in good shape:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/(css|js), roles: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/(_wdt|_profiler), roles: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create, roles: ROLE_EVENT_MANAGER }
            - { path: ^/, roles: ROLE_USER }