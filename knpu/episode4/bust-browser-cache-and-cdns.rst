Busting Browser Cache and Using a CDN
=====================================

But the ``asset`` function *does* give us some super-powers, like being able
to bust CSS and JS browser cache.

Open up ``app/config/config.yml`` and find the ``framework`` ``templating``
key. Uncomment out the ``assets_version`` key and set it to your favorite
star wars episode:

.. code-block:: yaml

    # app/config/config.yml
    # ...
    
    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: 5-return-of-the-jedi

.. note::

    We realized later that the number 6 (or even better VI) would have made
    a *little* bit more sense here...

When we view the source code, we've got a ``?5-return-of-the-jedi`` at the
end of the CSS file paths. That's handy!

.. code-block:: html

    <link href="/bundles/event/css/main.css?1" rel="stylesheet" />

Actually, this query parameter will be at the end of *everything* that uses
the the ``asset`` function. Since browser cache problems suck, increment this
number before you deploy and crush the problem. These aren't the assets
you're looking for.

If you want to get fancy, add an ``assets_version_format`` configuration option:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: 5-return-of-the-jedi
            assets_version_format: "%%s?v=%%s"

This looks a little funny, but has 2 ``%s`` placeholders. The first will
be filled in with the path to the asset and the second will get the version.
Refresh again and check out the path in the source code now:

    <link href="/bundles/event/css/main.css?1" rel="stylesheet" />

Head over to the `Reference section`_ of the Symfony docs and click into
the ``framework`` page. This shows you all the options that can live under
the ``framework`` key in ``config.yml``. 

Find the ``assets_version_format``. If you want to go really crazy, you can
follow the directions here and create URLs where the version is part of the
path, instead of a query parameter. You'd need to do some extra work with
rewrite rules to get things to load still, but some CDN's need this type
of cache busting.

Using a CDN
-----------

And on that note, we can use a CDN with pretty much no extra work. Add a
new ``assets_base_url`` key and give it some imaginary domain:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: 5-return-of-the-jedi
            assets_version_format: "%%s?v=%%s"
            assets_base_url: http://evilempireassets.com

Refresh! All the styling is gone, that's great! All the CSS files are prefixed
with my make-believe hostname.

.. code-block:: html

    <link rel="stylesheet"
        href="http://myfancycdn.com/bundles/event/css/event.css?v=5-return-of-the-jedi" />

All I'd need to do to make this work is upload my files to this CDN host. 
And actually, most CDN's support an "origin pull" configuration, where
it automatically downloads the files from your real server. There's no uploading
involved at all. Super easy.

Take the ``http:`` part off of the host name and view the source:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: 5-return-of-the-jedi
            assets_version_format: "%%s?v=%%s"
            assets_base_url: //myfancycdn.com

.. code-block:: html

    <link rel="stylesheet"
        href="//myfancycdn.com/bundles/event/css/event.css?v=5-return-of-the-jedi" />

This is a valid URL and makes sure that if the user is on an ``https`` page
on your site, that the CSS file is also downloaded via ``https``. This avoids
the annoying warnings about "non-secure" assets.

Ok, unbreak the site by commenting out this option:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: 5-return-of-the-jedi
            assets_version_format: "%%s?v=%%s"
            # assets_base_url: //myfancycdn.com

.. _`Reference section`: http://symfony.com/doc/current/reference/index.html
.. _`framework`: http://symfony.com/doc/current/reference/configuration/framework.html
