An Aside: Dependency Injection Parameters
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A "parameter" is a little variable that you can define and use inside ``config.yml``
or any other files where you define services. If you want to create a parameter,
just add a "parameters" key and then start adding some keys beneath it. We
can use it in any configuration file by surrounding it with two percent signs.

.. code-block:: yaml

    # app/config/config.yml
    # ...
    
    # an example of using a parameter
    parameters:
        routing_filename: routing.yml
    
    framework:
        # ...
        router:
            resource: "%kernel.root_dir%/config/%routing_filename%"

This is a great way to re-use information without repeating yourself.

We actually already have a bunch of parameters that we've defined in ``parameters.yml``
and used in ``config.yml``. One important note here is that there's nothing
special at all about ``parameters.yml``. It's imported just like any other
config file and we could even put its parameters right into ``config.yml``.
So then, why do we bother having the ``parameters.yml`` file? In the first
screencast we added ``parameters.yml`` to our ``.gitignore`` file so that
it won't be committed to the repository:

.. code-block:: text

    # .gitignore
    # ...
    app/config/parameters.yml

This means that every developer and every server will have its own copy of
this file. The ``parameters.yml`` file allows us to isolate all of our server-specific
configuration into one, small file.