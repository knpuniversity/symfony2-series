Bundles of Joy!
===============

*Bundles* are a hipster buzzword in the Symfony world. Yea, they're cool
but we really deserve all the credit! A bundle is just a place for us to
put our hard-earned code. We might make an EventBundle directory for that
feature and a UserBundle where we build the registration and login stuff.

We'll put anything and everything into a bundle: PHP code, config, templates,
CSS and cats. We can also put other people's bundles into our project. A
bundle in Symfony is similar to a plugin in other systems, but, ya know, way
more hipster.

The Console
-----------

Yes, we *can* create bundles manually. But I'd rather have someone else
do it for me. Meet ``console``: a magic executable file in the ``app/``.
Run it to see all of the tricks it knows:

.. code-block:: bash

    $ php app/console

Woh! All those green words are different console commands, including a lot
things that help you work with the database and debug. I like tools as much
as any programmer geek, so we'll use a lot of these over time.

Generating the EventBundle
--------------------------

For now run the ``generate:bundle`` command:

.. code-block:: bash

    $ php app/console generate:bundle

For the bundle namespace, type ``Yoda/EventBundle``. A bundle namespace always
has two parts: a vendor name and a name describing the bundle. In honor of
the Jedi master, we'll use "Yoda" for the first part and EventBundle for the
second. Unless you also work for Yoda, you'll probably use your company or
project name instead. Keep these as short as possible to save typing later.

Next, it wants a nickname for our bundle. We're going to be writing this
a lot, and lets face it, we're busy people. So, let's choose something short,
like ``EventBundle``. The only rule is that this ends with ``Bundle``.

Use the target default directory, but choose ``yml`` as the configuration
format. You'll just have to trust me on this part - we'll check out the
annotation configuration format later.

For the rest of the questions, just hit the enter key wildly. And once the
console-gnomes are finished, we have a brand new bundle.

What the Generator Did
~~~~~~~~~~~~~~~~~~~~~~

This did exactly three things for us.

First, it made a ``src/Yoda/EventBundle`` directory with some sample bundle
files.

Second, it plugged our bundle into the motherboard by adding a line in the
``AppKernel`` class.

Third, it added a line to the ``routing.yml`` file that imports routes from
the bundle. Contain your excitement: we're about 30 seconds from talking
about this part.

The PHPStorm Symfony Plugin
---------------------------

But I want to share a quick secret first. If you're using PHPStorm like I
am, I need you to download an aewsome `Symfony plugin`_. For everyone else,
this is totally *not* needed, it just adds some shortcuts.

Once it's installed, you need to activate it. We're now super-charged with
a ton of Symfony-specific help. You'll see this along the way.

.. _`Symfony plugin`: http://plugins.jetbrains.com/plugin/7219?pr=phpStorm
