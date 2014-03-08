Inserting and Querying Data
===========================

We can use the play script to create and save our first ``Event`` object.
Start by importing the ``Event`` class's namespace::

    // ...
    // all our setup is done!!!!!!
    use Yoda\EventBundle\Entity\Event;

Let's flex our mad PHP skills and put some data on each property. Remember,
this is just a normal PHP object, it doesn't have any jedi magic and doesn't
know anything about a database::

    use Yoda\EventBundle\Entity\Event;

    $event = new Event();
    $event->setName('Darth\'s surprise birthday party');
    $event->setLocation('Deathstar');
    $event->setTime(new \DateTime('tomorrow noon'));
    $event->setDetails('Ha! Darth HATES surprises!!!!');

Let's save this wild event! To do that, we use a special object called the
"entity manager". It's basically the most important object in Doctrine and
is in charge of saving objects and fetching them back out. To get the entity
manager, first grab a service from the container called ``doctrine`` and
call ``getManager`` on it::

    $em = $container->get('doctrine')->getManager();

Saving is a two-step process: ``persist()`` and then ``flush()``::

    $em = $container->get('doctrine')->getManager();
    $em->persist($event);
    $em->flush();

Two steps! Yea, and for an awesome reason. The first tells Doctrine "hey,
you should know about this object". But no queries are made yet. When we
call ``flush()``, Doctrine actually executes the INSERT query.

The awesome is that if you need to save a bunch of objects at once, you can
persist each of them and call flush once. Doctrine will then pack these operations
into as few queries as possible.

Now, when we execute our play script, it blows up!

  PDOException: SQLSTATE[42000] [1049] Unknown database 'symfony'

Scroll up to see the error message: "Unknown database symfony". Duh! We skipped
one important step: setting up the database config.

Configuring the Database
------------------------

Database config is usually stored in ``app/config/parameters.yml``. Change
the database name to "yoda_event". For my super-secure computer, the database
user ``root`` with no password is perfect:

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

We can haz Database
~~~~~~~~~~~~~~~~~~~

Now, I know you're super-smart and capable, but let's be lazy again and use
the console to create the database for us with the ``doctrine:database:create``
command:

.. code-block:: bash

    $ php app/console doctrine:database:create

There's also a command to drop the database. That's great, until you realize
that you just ran it on production... and everyone is running around with
their hair on fire. Yea, so that's a healthy reminder to not give your production
db user access to drop the database.

A Table for Events, Please
~~~~~~~~~~~~~~~~~~~~~~~~~~

We have a database, but no tables. Any ideas who might help us with this?
Oh yeah, our friend console! Run the ``doctrine:schema:create`` command.
This finds all your entities, reads their annotation mapping config, and
creates all the tables:

.. code-block:: bash

    $ php app/console doctrine:schema:create

Time to try out the play script again:

.. code-block:: bash

    $ php play.php

What? No errors! Did it work? Use the `doctrine:query:sql` command to run
a raw query against the database:

.. code-block:: bash

    $ php app/console doctrine:query:sql "SELECT * FROM yoda_event"

And voila! There's our event.

Making nullable Fields
----------------------

Let's get crazy and leave the ``details`` field blank::

    // play.php
    // ...
    $event->setTime(new \DateTime('tomorrow noon'));
    //$event->setDetails('Ha! Darth HATES surprises!!!!');

When we run the script, another explosion! Scrolling up, the error straight
from MySQL saying that the ``details`` column can't be null.

  SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'details' cannot be null

So Doctrine assumes by default that all of your columns should be set to ``NOT NULL``
when creating the table. To change this, add a ``nullable`` option to the ``details``
property inside the entity::

    // src/Yoda/EventBundle/Entity/Event.php

    /**
     * @ORM\Column(name="details", type="text", nullable=true)
     */
    private $details;

    // ...

.. tip::

    Doctrine has a killer page that shows all of the annotations and their
    options. See `Annotations Reference`_.

But before this does anything, the actual column in the database needs to
be modified to reflect the change. Hey, console to the rescue! Run the
``doctrine:schema:update`` command:

.. code-block:: bash

    $ php app/console doctrine:schema:update

This is pretty sweet: it looks at your annotations mapping config, compares
it against the current state of the database, and figures out exactly what
queries we need to run to update the database structure.

But the command didn't do anything yet. Pass ``--dump-sql`` to see the queries
it wants to run and ``--force`` to actually run them:

.. code-block:: bash

    $ php app/console doctrine:schema:update --force

Run the play script again. Alright, no errors means that the new event is
saved without a problem.

Querying for Objects
--------------------

Putting stuff into the database is nice, but let's learn how to get stuff
out. Open up the ``DefaultController`` class we've been playing with. First,
we need to get the all-important entity manager. That's old news for us.
Like before, just get the ``doctrine`` service from the container and call
``getManager`` on it.

This works, but since we're extending the base controller, we can use its
``getDoctrine()`` to get the ``doctrine`` service. That'll save us a few
keystrokes::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    public function indexAction($count, $firstName)
    {
        // these 2 lines are equivalent
        // $em = $this->container->get('doctrine')->getManager();
        $em = $this->getDoctrine()->getManager();

        // ...
    }

To query for something, we always first get an entity's repository object::

    public function indexAction($count, $firstName)
    {
        // these 2 lines are equivalent
        // $em = $this->container->get('doctrine')->getManager();
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('EventBundle:Event');

        // ...
    }

A repository has just one job: to help query for one type of object, like
Event objects. The ``EventBundle:Event`` string is the same top-secret shortcut
syntax we used when we generated the entity - it's like the entity's nickname.

.. tip::

    If you like typing, you can use the full class name anywhere the entity
    "alias" is used:

        $em->getRepository('Yoda\EventBundle\Entity\Event');

Use the repository's ``findOneBy`` method to get an ``Event`` object by name.
There are other shortcut methods too, like ``findAll``, ``findBy``, and ``find``::

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

.. tip::

    In `Episode 2`_, we'll add more methods to the repository and write some
    custom queries.

Rendering Entities in Twig
~~~~~~~~~~~~~~~~~~~~~~~~~~

Ok - let's pass the Event object into the template as a variable. We can
use Twig's render syntax to print out the name and location properties. Internally,
Twig is smart enough to call ``getName`` and ``getLocation``, since the properties
are private:

.. code-block:: html+jinja

    {% block body %}
        {# ... #}
        
        {{ event.name }}<br/>
        {{ event.location }}<br/>
        
    {% endblock %}

Refresh the page! I can see our event data, so all the magic Doctrine querying
must be working. Actually, check out out the web debug toolbar. The cute
box icon jumped from zero to one, which is the number of queries used for
the page. When we click the little boxes, we can even see what those queries
are and even run ``EXPLAIN`` on them.

Good work young jedi! Seriously, you know the basics of Doctrine, and that's
not easy. In the next 2 episodes, we'll create custom queries and use cool
things like events that let you "hook" into Doctrine as entities are inserted,
updated or removed from the database.

Oh, and don't forget `Doctrine has its own documentation`_, though the most
helpful pages are the `Annotations Reference`_ and `Doctrine Mapping Types`_
reference pages. And by the way, when you see annotations in the Doctrine
docs, prefix them with ``@ORM\`` before putting them in Symfony. That's
because of this ``use`` statement above our entity::

    // src/Yoda/EventBundle/Entity/Event.php
    use Doctrine\ORM\Mapping as ORM;
    // ..

If that's in your class and you have ``@ORM\`` at the start of all of your
Doctrine annotations, you're killing it.

.. _`Doctrine has its own documentation`: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html
.. _`Annotations Reference`: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/annotations-reference.html
.. _`Episode 2`: http://knpuniversity.com/screencast/symfony2-ep2/repository#doctrine-s-querybuilder
.. _`Doctrine Mapping Types`: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html#doctrine-mapping-types
