An Aside: Dependency Injection Parameters
=========================================

Cleanse the palette of all the forms stuff and open ``config.yml``. Under
the ``doctrine`` key, we see a bunch of percent sign values:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    doctrine:
        dbal:
            driver:   "%database_driver%"
            host:     "%database_host%"
            port:     "%database_port%"
            dbname:   "%database_name%"
            user:     "%database_user%"
            password: "%database_password%"
            # ...

Whenever you see something surrounded by two percent signs in a config file,
it's a parameter. Parameters are like variables: you set them somewhere and
then use them with this syntax. So where are these being set?

Open up ``parameters.yml`` to find the answer:

.. code-block:: yaml

    # app/config/parameters.yml
    # ...

    # This file is auto-generated during the composer install
        database_driver: pdo_mysql
        database_host: 127.0.0.1
        database_port: null
        database_name: knp_events
        database_user: root
        database_password: null
        # ...

In `episode 1`_, we talked about how this file is special because it holds
any server-specific configuration. This works because it's in our ``.gitignore``
file so that every developer and server can have their own. So we set parameters
here and use them anywhere else.

Adding More Parameters
----------------------

But technically, we can add parameters to *any* configuration file. Go back
to ``config.yml`` and add a new ``parameters`` key anywhere in the file. Below
it, create a new parameter called ``our_assets_version``, and set it to the
``assets_version`` value we're using below:

.. code-block:: yaml

    # app/config/config.yml
    imports:
        - { resource: parameters.yml }
        - { resource: security.yml }
        - { resource: "@EventBundle/Resources/config/services.yml" }
        - { resource: "@UserBundle/Resources/config/services.yml" }

    parameters:
        our_assets_version: 5-return-of-the-jedi

    framework:
        # ...

Now, just use it under the ``framework`` key:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: %our_assets_version%
            assets_version_format: "%%s?v=%%s"
        # ...

See, they work just like variables. Refresh to make sure we didn't break
anything.

So now you know what these percent signs are all about. Spoiler alert! You can 
also access parameters from a controller using ``$this->container->getParameter``, 
which might come in handy.

.. _`episode 1`: http://knpuniversity.com/screencast/symfony2-ep1/installation#setting-up-git
