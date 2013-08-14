Databases and Doctrine
======================

For most apps, working with a database is a must. Symfony integrates a third-party
library - called Doctrine - which helps make this easy. Doctrine has its
own website and documentation, though
`documentation about working with Doctrine inside Symfony`_ is also available
at symfony.com, and quite friendly.

Doctrine is an ORM or "object-relational mapper". This fancy term means that
instead of thinking about database tables and rows, we'll get to think about
objects. Wouldn't it be nice if you could create PHP objects, save them
somewhere, and then fetch the objects later? That's exactly what Doctrine
does. It ultimately stores the data in a relational database like MySQL,
but that happens behind the scenes.

Creating the Event Entity Class
-------------------------------

So, forget about the database! Instead, let's create a plain ``Event`` class
with a few fields like ``name`` and ``location``. We'll let Symfony build
the class for us by calling the ``doctrine:generate:entity`` console command:

.. code-block:: bash

    php app/console doctrine:generate:entity

This command actually does two things at once: it generates a new ``Event``
class as well as some configuration that says exactly how Doctrine should
store event objects in the database.

In step 1 of the command, enter ``EventBundle:Event``. This is a shortcut name
for the ``Event`` class that's about to be generated. It basically means that
you want to generate an ``Event`` class inside the ``EventBundle``.

Next, choose annotation as the configuration format and then move on to field
creation. For now, add the following fields:

* ``name`` as a string field;
* ``imageName`` as a string field;
* ``time`` as a datetime field;
* ``location`` as a string field;
* and ``details`` as a text field.

This step tells Doctrine two things: what properties the ``Event`` class will
have and how each should be represented in the database.

Finally, choose "yes" for the repository class, which we'll eventually use
to house some custom queries for ``Event`` objects.

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

Notice that it lives in the ``Entity/`` directory. The word "entity" describes
a normal PHP class that happens to be saved to a database by Doctrine. So,
when you hear me say "entity", remember that I'm just referring to a PHP class.

Inside the class, if you ignore the PHP comments, you'll see that this is
a plain old PHP class. It doesn't do anything - it just stores data on its
private properties. Getter and setter methods - like ``getName()`` and ``setName()`` -
were generated for each field so that we can play with an event's data. It's
pretty underwhelming, which is what makes Doctrine so interesting.

Now, check out the PHP comments near the top of the class. These comments
are called  "annotations", and they're actually read and parsed by Doctrine
so that it knows exactly how it should save an ``Event`` object to the database.
Right now, events will save to an "event" table and each property will be a field
in that table. I usually like to prefix all of my table names, so let's do that by 
adding a name option to the Table annotation::

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

Great, let's see how this actually works! First, I'm going to show you a really
handy way that you can play and test code with Symfony, without worrying about
routes and controllers. First, copy the ``app_dev.php`` file into the root
of your project and rename it to ``play.php``. Open the file and remove the IP
protection stuff at the top and update the require paths since we moved the
file up one directory. This script boots Symfony and then tries to handle
the incoming request. Let's short-circuit it so that it only boots Symfony.
Replace the last three lines with simply ``$kernel->boot()``.

Remember the service container from earlier? We have access to it here. To
make it as flexible as possible, I'll add a few lines that help fake a real
request. This is a little jedi mind trick so don't worry about what these
do right now::

    // play.php
    use Symfony\Component\HttpFoundation\Request;
    umask(0000);

    $loader = require_once __DIR__.'/app/bootstrap.php.cache';
    require_once __DIR__.'/app/AppKernel.php';

    $kernel = new AppKernel('dev', true);
    $kernel->loadClassCache();
    $request = Request::createFromGlobals();
    $kernel->boot();

    $container = $kernel->getContainer();
    $container->enterScope('request');
    $container->set('request', $request);

    // all our setup is done!!!!!!

Now, let's play!, Grab the ``templating`` service and render the ``index.html.twig``
template::

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

We can execute the play script easily from the command line:

.. code-block:: bash

    php play.php

When I run it, the template is rendered and printed out. Pretty cool, right?

Inserting Data into the Database
--------------------------------

Let's use the play script to work with Doctrine and our new ``Event`` entity
class. First, import the ``Event`` class's namespace::

    // ...
    // all our setup is done!!!!!!
    use Yoda\EventBundle\Entity\Event;

Now, let's create a new instance and set some data on each property. This is
just a normal PHP object, it doesn't have any jedi magic and doesn't know
anything about a database::

    use Yoda\EventBundle\Entity\Event;
    $event = new Event();
    $event->setName('Darth\'s surprise birthday party');
    $event->setLocation('Deathstar');
    $event->setTime(new \DateTime('tomorrow noon'));
    $event->setDetails('Ha! Darth HATES surprises!!!!');
    $event->setImageName('foo.jpg');

To actually persist the ``Event`` object to the database, we'll use a special
object called the "entity manager". The entity manager is the most important
object in Doctrine: it's in charge of saving objects as well as fetching them
back out. To get the entity manager, we'll grab a service from the container
called ``doctrine`` and call ``getManager`` on it::

    $em = $container->get('doctrine')->getManager();

Using the entity manager, we'll save the ``Event`` object by calling ``persist``
and then ``flush``. Why two steps? The first tells Doctrine to "manage" the
object. No queries are made, but Doctrine is now "watching" your object for
changes. When we call ``flush``, Doctrine actually executes the INSERT query.
Separating this into two steps means you could create a bunch of event objects
and insert them all at once. We'll see this when we add fixtures later::

    $em = $container->get('doctrine')->getManager();
    $em->persist($event);
    $em->flush();

Now, when we execute our play script, it blows up!

  PDOException: SQLSTATE[42000] [1049] Unknown database 'symfony'

Scroll up to see the error message: "Unknown database symfony". Of course!
We skipped one important step: setting up the database config.

Configuring the Database
~~~~~~~~~~~~~~~~~~~~~~~~

By default, database config is stored in the ``app/config/parameters.yml``
file. Let's change the database name to "yoda_event". For my local box, the
database user and password are fine:

.. code-block:: yaml

    # app/config/parameters.yml
    parameters:
        database_driver:   pdo_mysql
        database_host:     localhost
        database_port:     ~
        database_name:     yoda_event
        database_user:     root
        database_password: ~
        # ...

I usually have Symfony create the new database for me by running the
``doctrine:database:create`` command:

.. code-block:: bash

    php app/console doctrine:database:create

You can also drop the database and re-create it. This is really handy when
developing, but also a solid reason why your production database user probably
shouldn't have the ability to drop your database.

Ok, we have a database, but no tables. To add the tables, run the ``doctrine:schema:create``
command. This finds all of your entities, reads their mapping information,
and creates all the tables you need:

.. code-block:: bash

    php app/console doctrine:schema:create

Let's try our play script again:

.. code-block:: bash

    php play.php

No errors! To see if it worked, we can use the `doctrine:query:sql` command
to run a raw query against the database. And voila! The event saved perfectly:

..code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM yoda_event"

Making nullable Fields
----------------------

In the play script, let's leave the ``imageName`` field blank and try to
insert another record::

    // play.php
    // ...
    $event->setDetails('Ha! Darth HATES surprises!!!!');
    //$event->setImageName('foo.jpg');

And now, this one blows up! Scrolling up, the error says that the ``imageName``
column can't be null.

  SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'imageName' cannot be null

By default, Doctrine assumes that all of your fields should be set to ``NOT NULL``
in the database. To fix this, let's add ``nullable`` to a few of the ``Event``
properties::

    // src/Yoda/EventBundle/Entity/Event.php

    /**
     * @ORM\Column(name="imageName", type="string", length=255, nullable=true)
     */
    private $imageName;

    /**
     * @ORM\Column(name="details", type="text", nullable=true)
     */
    private $details;

    // ...

Now that we've fixed that in code, the database structure needs to be altered
to reflect the change. A really easy way to do this is with the ``doctrine:schema:update``
command.

.. code-block:: bash

    php app/console doctrine:schema:update

This command is *awesome* - it looks at all of your entity mapping information,
compares it against the current state of your database, and figures out exactly
what queries need to be run to update your database structure. Without any
options, the command doesn't actually do anything. Pass ``--dump-sql``
to see the queries it wants to run and ``--force`` to actually run them:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Now, when we run the play script, the new event is saved without a problem.

Querying for Objects
--------------------

Quickly, let's see how we can get objects back out of the database. Head
to the ``DefaultController`` class that we've been playing with. First, let's
get the entity manager by getting the ``doctrine`` service out of the container
and calling ``getManager``. If you're extending the base controller class
like we are, you can also use ``$this->getDoctrine()`` to get the Doctrine
service. This doesn't save you many keystrokes, but will give you autocompletion
in some editors::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    public function indexAction($count, $firstName)
    {
        // these 2 lines are equivalent
        // $em = $this->container->get('doctrine')->getManager();
        $em = $this->getDoctrine()->getManager();

        return $templating->renderResponse(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );
    }

To grab an ``Event`` object from the database, we'll first ask the entity
manager for a "repository". A repository is an object whose job is to help
you fetch one specific class of objects. In this case, the repository object
helps us return ``Event`` objects. Once we have it, we can use its `findOneBy`
method to get an ``Event`` object by name. The repository has a few other useful
methods, like `findAll`, `findBy`, and `find`::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    public function indexAction($count, $firstName)
    {
        // these 2 lines are equivalent
        // $em = $this->container->get('doctrine')->getManager();
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('EventBundle:Event');
        
        $event = $repo->findOneBy(array(
            'name' => 'Darth\'s surprise birthday party',
        ));

        return $templating->renderResponse(
            'EventBundle:Default:index.html.twig',
            array(
                'name' => $firstName,
                'count' => $count,
                'event'=> $event,
            )
        );
    }

In another screencast we'll learn how to add our own methods with custom 
queries to the repository.

To render some of the event's data, pass it as a variable into the template.
Now, we can use Twig's render syntax to print out the name and location properties.
Internally, Twig is smart enough to call ``getName`` and ``getLocation`` to get
the data:

.. code-block:: html+jinja

    {% block body %}
        {# ... #}
        
        {{ event.name }}<br/>
        {{ event.location }}<br/>
        
    {% endblock %}

When we refresh, we'll see the event information. But checkout out the web
debug toolbar: you'll see that the query count jumped from zero to two. Click
the icon to see the queries, including the one executed when we used the
repository. Use this to make sure your pages aren't getting too heavy with
queries.

Good work young jedi! Now that you know the basics of Doctrine, you're getting
pretty dangerous. We still need to talk about creating custom queries, storing
those in your own repository classes, and cool things like lifecycle callbacks
which let you "hook" into Doctrine before and after entities are saved, updated,
and removed from the database. Some of this is a little more advanced, so
we will see it in future screencasts.

And remember, `Doctrine has its own documentation`_. If you read it, be aware
that there are a few differences when working with Symfony. The most important
involve annotations. In Symfony, all annotations must start with ``@ORM\``,
and you need the ORM ``use`` statement at the top of your class. So, when
translating code from the Doctrine documentation, be sure to add the ORM
prefix and the ``use`` statement.

.. _`documentation about working with Doctrine inside Symfony`: http://symfony.com/doc/current/book/doctrine.html
.. _`Doctrine has its own documentation`: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html