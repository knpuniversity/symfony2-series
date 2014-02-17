Bundles of Joy!
===============

The first step to building our app is to create an "event" bundle. A bundle
is just a directory that contains all the code for a single feature. And
I do mean *all* the code, from your stuffy PHP classes all the way to the CSS
and JS files that bring the feature to life. This is the *first* great thing
about bundles: your features are naturally organized and isolated. If we decide later
that we need to reuse our event bundle for another project, it's pretty easy.

The Console
-----------

Instead of creating the bundle manually, we'll let Symfony do it for us.
Our project comes with a console that's full of commands that let you generate
code, run commands against your database, or help you debug. Unless you're
on Windows, you can call the console with the shorter "shebang" syntax:

.. code-block:: bash

    php app/console

One of the features of the console is the ``help`` command. Use it to see
the description, options and example usage of a command:

.. code-block:: bash

    php app/console help

Generating the EventBundle
--------------------------

We'll see a lot more of these commands in action as we go along. For now
run the ``generate:bundle`` command:

.. code-block:: bash

    php app/console generate:bundle

For the bundle namespace, type ``Yoda/EventBundle`` A bundle namespace always
has two parts: a vendor name and a name describing the bundle. In honor of
the Jedi master, we'll use "Yoda" for the first part and EventBundle for the
second. Unless you also work for Yoda, you'll probably use your company or
project name instead. Keep these names as short as possible to save typing
later.

Next, we need to choose a nickname for our bundle. We'll use the name all
over the place, so the shorter, the better. Symfony's going to try to suggest
a rather long nickname (``YodaEventBundle``). I usually shorten it by removing
the vendor name (``EventBundle``). The only rule is that the nickname has
to end in ``Bundle``, which is nice for consistency anyways.

Next, use the target default directory, but choose ``yml`` as the configuration
format. We'll talk about what this means later and show you some of the cool
things you can do with annotations. Hit enter for the rest of the generator's
questions.

The generator did exactly three things for us. First, it generated a bundle
skeleton in the ``src/Yoda/EventBundle`` directory with a sample page and a
few other things. Second, it activated the bundle by adding a line to the
``AppKernel`` class. Third, it added a line to the ``routing.yml`` file, which
imports another routing file that's inside the bundle. We'll talk about
routing next.

The PHPStorm Symfony Plugin
---------------------------

But first, if you *are* using PHPStorm, I'd highly recommend downloading
and installing the Symfony plugin. Once it's installed, just make sure it's
enabled for this project. This will give you a ton of Symfony-specific help
while we're developing.
