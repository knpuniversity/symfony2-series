Databases and Doctrine
======================

Symfony doesn't care about your database or the code you use to talk to it.
Seriously. It's not trying to be rude, but other libraries already solve
this problem. So if you want to make a `:phpclass:PDO` connection and run
raw SQL queries, that's great! When we create services in `Episode 3`_, you'll
learn some life-saving strategies to organize something like this.

But most people that use Symfony use a third-party library called Doctrine.
It has its own `website and documentation`_, though `Symfony's Doctrine documentation`_
is a lot friendlier.

In a nutshell, Doctrine maps rows and columns in your database to objects
and properties in PHP. Imagine we have an ``Event`` object with ``name``
and ``location`` properties. If we tell Doctrine to save this object, it
inserts a row into a table and puts the data on ``name`` and ``location``
columns. And when we query for the event, it puts the column data back onto
the properties of an Event object.

The big confusing mind-switch is to stop thinking about tables and start thinking
about PHP classes.

Creating the Event Entity Class
-------------------------------

In fact, let's create the ``Event`` class we were talking about. The console
can even make this for us with the ``doctrine:generate:entity`` command:

.. code-block:: bash

    $ php app/console doctrine:generate:entity

Like other commands, this one is self-aware and will start asking you questions.
In step 1, enter ``EventBundle:Event``. This is another top-secret shortcut
name and it means you want the ``Event`` class to live inside the ``EventBundle``.

Now, choose annotation as the configuration format and move on to field
creation. Add the following fields:

* ``name`` as a string field;
* ``time`` as a datetime field;
* ``location`` as a string field;
* and ``details`` as a text field.

These types here are configuration that tell Doctrine how each property should
be stored in the database.

If you messed anything up, panic! Or just exit with ``ctrl+c`` try the command
again. Nothing happens until it finishes.

.. note::

    All of the Doctrine data types are explained in their documentation:
    `Doctrine Mapping Types`_.

Say "yes" for the repository class and confirm generation. A repository is
a cool guy we'll use later to store custom queries.

What just Happened?
~~~~~~~~~~~~~~~~~~~

Ok! So what did that do? Actually, it just created 2 new classes in an ``Entity``
directory in our bundle. And that's it.

Check out the new ``Event`` class::

    // src/Yoda/EventBundle/Entity/Event.php
    namespace Yoda\EventBundle\Entity;

    /**
     * @ORM\Table()
     * @ORM\Entity(repositoryClass="Yoda\EventBundle\Entity\EventRepository")
     */    
    class Event
    {
        /**
         * @ORM\Column(name="id", type="integer")
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        private $id;

        /**
         * @ORM\Column(name="name", type="string", length=255)
         */
        private $name;

        // ...

        public function getName()
        {
            return $this->name;
        }

        public function setName($name)
        {
            $this->name = $name;
        }
        
        // ...
    }

For Doctrine, the word "entity" means a normal PHP class that we will save
to the database. So, whenever I say "entity", just scream: "that's just
a normal PHP class!". Your co-workers will love you!

If you ignore the PHP comments, you'll see that this is a plain old PHP class.
It doesn't do anything: it just stores data on its private properties. Getter
and setter methods - like ``getName()`` and ``setName()`` - were generated
so we can play with an event's data. It's underwhelming, almost disappointing,
and that's what makes Doctrine so interesting.

Now, check out the PHP comments above the class. These comments are called
"annotations", and they're actually read and parsed by Doctrine. So when
you hear "annotations", shout "PHP comments that are read like configuration!".

These tell Doctrine *how* it should save an ``Event`` object to the database.
Right now, they will save to an ``event`` table and each property will be
a column in that table. I usually like to prefix all of my table names, so
let's do that by adding a name option to the ``Table`` annotation::

    /**
     * @ORM\Table(name="yoda_event")
     * @ORM\Entity(repositoryClass="Yoda\EventBundle\Entity\EventRepository")
     */    
    class Event
    {
        // ...
    }

Creating the "play" Script
--------------------------

We're ready to insert data, but first I want to show you a debugging trick.
First, copy the ``web/app_dev.php`` file to the root of the project and
rename it to ``play.php``:

.. code-block:: bash

    $ cp web/app_dev.php play.php

Open it up and remove the IP protection stuff at the top and update the require
paths since we moved things around::

    // play.php
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Debug\Debug;
    umask(0000);

    $loader = require_once __DIR__.'/app/bootstrap.php.cache';
    Debug::enable();

    require_once __DIR__.'/app/AppKernel.php';
    // ...

This script boots Symfony, processes the request, and spits out the page.
But I have evil plans to transform it into a debugging monster where we can
write random code and execute it from the command line to see what happens.

Replace the last three lines with ``$kernel->boot()``::

    // ...
    require_once __DIR__.'/app/AppKernel.php';

    $kernel = new AppKernel('dev', true);
    $kernel->loadClassCache();
    $request = Request::createFromGlobals();
    $kernel->boot();

Remember the service container from earlier? We have access to it here. To
make it as flexible as possible, I'll add a few lines that help fake a real
request. This is a little jedi mind trick so don't worry about what these
do right now::

    // play.php
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Debug\Debug;
    umask(0000);

    $loader = require_once __DIR__.'/app/bootstrap.php.cache';
    Debug::enable();

    require_once __DIR__.'/app/AppKernel.php';

    $kernel = new AppKernel('dev', true);
    $kernel->loadClassCache();
    $request = Request::createFromGlobals();
    $kernel->boot();

    $container = $kernel->getContainer();
    $container->enterScope('request');
    $container->set('request', $request);

    // all our setup is done!!!!!!

Our evil creation is alive! So let's play around. How could we render a template
here? Why, just by grabbing the ``templating`` service and using its ``render()``
method::

    // ...
    // all our setup is done!!!!!!
    $templating = $container->get('templating');
    
    echo $templating->render(
        'EventBundle:Default:index.html.twig',
        array(
            'name' => 'Yoda',
            'count' => 5,
        )
    );

Execute the play script from the command line.

.. code-block:: bash

    $ php play.php

When I run it, the template is rendered and printed out. How cool is that?
This is perfect for whenever we need to quickly test out some code.

.. _`Symfony's Doctrine documentation`: http://symfony.com/doc/current/book/doctrine.html
.. _`website and documentation`: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html
.. _`Episode 3`: http://knpuniversity.com/screencast/symfony2-ep3/services
.. _`Doctrine Mapping Types`: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html#doctrine-mapping-types
