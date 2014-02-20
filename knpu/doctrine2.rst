Inserting and Querying Data
---------------------------

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
        database_host:     127.0.0.1
        database_port:     null
        database_name:     yoda_event
        database_user:     root
        database_password: null
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

In the play script, let's leave the ``details`` field blank and try to
insert another record::

    // play.php
    // ...
    $event->setTime(new \DateTime('tomorrow noon'));
    //$event->setDetails('Ha! Darth HATES surprises!!!!');

And now, this one blows up! Scrolling up, the error says that the ``details``
column can't be null.

  SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'details' cannot be null

By default, Doctrine assumes that all of your fields should be set to ``NOT NULL``
in the database. To fix this, let's add ``nullable`` to the field in the ``Event``
entity::

    // src/Yoda/EventBundle/Entity/Event.php

    /**
     * @ORM\Column(name="details", type="text", nullable=true)
     */
    private $details;

    // ...

--> Add note here about finding annotations reference?

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

        return $this->render(
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
debug toolbar: you'll see that the query count jumped from zero to one. Click
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

.. _`Doctrine has its own documentation`: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html
