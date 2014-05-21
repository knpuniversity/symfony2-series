Security: Creating Roles and Role Hierarchies
---------------------------------------------

Let's change gears and mention a few more things about security. Earlier,
we saw how you could enforce security in two different ways. The ``access_control``
method is the easiest, but we can always enforce the logic manually anywhere
else in our code. In both cases, we're checking whether or not a user has
a specific role. If they do, they get access. If they don't, they'll see
the login page or the access denied screen.

In our example, we showed a pretty basic system with just ``ROLE_USER`` and
``ROLE_ADMIN.`` If you need another role, just start using it. For example,
if only *some* users are able to create events, we can protect event creation
with a new role called ``ROLE_EVENT_MANAGER``:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create , roles: ROLE_EVENT_MANAGER }

The *only* rule when creating a role is that it *must* start with ``ROLE_``.
If it doesn't, you won't get an error, but security won't be enforced. 

Try it out by logging in as admin, and trying to create an event. Our admin
user has ``ROLE_USER`` and ``ROLE_ADMIN``, but not ``ROLE_EVENT_MANAGER``.
If we want to give all administrators the ability to create events, we can
take advantage of role hierarchy. Add ``ROLE_EVENT_MANAGER`` to ``ROLE_ADMIN``
and refresh:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        role_hierarchy:
            ROLE_ADMIN:       [ROLE_USER, ROLE_EVENT_MANAGER]
            ROLE_SUPER_ADMIN: [ROLE_USER, ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]

We are in, now let's schedule that wookiee wine down!

Strategies for Controller Access
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Keep these two tips in mind when using roles:

1. Protect the actual parts of your application using feature-specific roles,
   not user-specific roles. This means your roles should describe the features
   they give you access to and not the type of user that has that access.

2) Use the role hierarchy section to manage which types of users have which
   roles. For example, you might decide that ``ROLE_USER`` has ``ROLE_BLOGGER``
   and ``ROLE_EVENT_MANAGER``. Give your actual users these user-specific roles.

By following these tips, you'll be able to easily control the exact areas
of your site that different users have access to.
