Doctrine Extensions: Sluggable and Timestampable
================================================

I want to show you a little bit of Doctrine magic by using an open source
library called `DoctrineExtensions`_. The first bit of magic we'll add is
a ``slug`` to Event. Not the jabba the hutt variety, but a property that
is automatically cleaned and populated based on the event name.

Installing the StofDoctrineExtensionsBundle
-------------------------------------------

Head over to `knpbundles.com`_ and search for ``doctrine extension``. The
`StofDoctrineExtensionsBundle`_ is what we want: it brings in that DoctrineExtensions
library and adds some Symfony glue to make things really easy.


Installing a bundle is always the same  3steps. First, use Composer's ``require``
command and pass it the name of the library:

.. code-block:: bash

    php composer.phar require stof/doctrine-extensions-bundle

If it asks you for a version, type "FOOOOOOOOOOOOOOO". In the future, Composer
should decide the best version for you.

Like we've seen before, the ``require`` command just added the library to
``composer.json`` for us and started downloading it.

Second, add the new bundle to your ``AppKernel``::

    // app/AppKernel.php
    // ...

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
        );

        // ...
    }

And third, configure the bundle by copying a few lines from the README:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    stof_doctrine_extensions:
        orm:
            default:    ~

Adding Sluggable to Event
-------------------------

This bundle brings in a bunch of cool features, which we have to activate
manually here. The first is called "sluggable":

.. code-block:: yaml

    # app/config/config.yml
    # ...

    stof_doctrine_extensions:
        orm:
            default:
                sluggable:   true

Open up the ``Event`` entity and add a new property called ``slug``::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\Column(length=255, unique=true)
     */
    protected $slug;

This is a normal property which will contain a URL-safe and unique version
of the event's name. Add the getter and setter::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

Next, we're going to configure the ``slug`` to be set automatically by the
doctrine extensions library.

The ``StofDoctrineExtensionBundle`` is actually just a wrapper around another
library which does most of the work. We can `go to its README`_ to get real
usage details. Find the ``sluggable`` section and look at the first example.
This library works via annotations, so copy and paste the new ``use`` statement
into ``Event`. Next, copy the annotation from the slug field and change the
fields option to only include ``name``::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...
    
    use Gedmo\Mapping\Annotation as Gedmo;
    // ...
    
    class Event
    {
        // ...

        /**
         * @Gedmo\Slug(fields={"name"}, updatable=false)
         * @ORM\Column(length=255, unique=true)
         */
        protected $slug;
    }

This tells the extensions library to automatically set the ``slug`` based
on the ``name`` property. By adding ``updatable`` equals false, we're telling
the library to set ``slug`` once and never change it again, even if the event's
name changes. This is a good idea because the slug will be used in the event's
URL, which we don't want to change.

Let's try it! Update the database schema and then reload your fixtures:

.. code-block:: bash

    php app/console doctrine:schema:update --force
    php app/console doctrine:fixtures:load

If you get an integrity constraint error, just drop your schema and rebuild it:
our existing events will all have blank slugs, which causes an issue:

.. code-block:: bash

    php app/console doctrine:schema:drop
    php app/console doctrine:schema:create
    php app/console doctrine:fixtures:load

If we check the results, we'll see the the new ``slug`` column is automatically set
to a normalized, URL safe version of the name. As an added bonus, if two
events have the same name, the library will automatically add a ``-1`` to the
end of one of the slugs. The library makes sure that these are always unique.

Using the slug in the Event URL
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let's put this new magic to use. Change the ``event_show`` route to use the
``slug`` instead of the ``id``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...

    event_show:
        pattern:  /{slug}/show
        defaults: { _controller: "EventBundle:Event:show" }

    # ...

Update the ``showAction`` accordingly and query for the ``Event`` using the
slug::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function showAction($slug)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EventBundle:Event')
            ->findOneBy(array('slug' => $slug));

        // ...

        // also change this line, since the $id variable is gone
        $deleteForm = $this->createDeleteForm($entity->getId());
        // ...
    }

Since we changed the route, we may need to update it in a few other places.
I'll use the "git grep" command to quickly uncover the three other places
the route is used:

.. code-block:: bash

    git grep event_show

Update each to pass in the ``slug`` instead of the ``id``:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/edit.html.twig #}
    {# ... #}

    <a class="link" href="{{ path('event_show', {'slug': entity.slug}) }}">show event</a>

.. note::

    You'll also need to make a similar change in ``EventController::createAction``
    and the ``EventBundle::Event::index.html.twig`` template.

Head back to the homepage of our app in your browser. Now, when we click on
an event, we have a beautiful URL.

Timestampable: created and updated Fields
-----------------------------------------

Let's use some more magic from the extensions library. One good habit to get
into is to have ``created`` and ``updated`` fields on every table in your database.
This behavior is called ``timestampable`` - enable it in ``config.yml``.

.. code-block:: yaml

    stof_doctrine_extensions:
        default_locale: en_US
        orm:
            default:
                sluggable: true
                timestampable: true

Head to the `timestampable section of the documentation`_ to see how this works.
We already have the ``Gedmo`` annotation, so just copy in the ``created`` and
``updated`` properties::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

Like always, these are normal properties, so generate the getters and setters.

.. tip::

    Generating the getters and setters can be done either via your IDE or
    by running the ``php app/console doctrine:generate:entities`` command.

Next, update the database schema to add the two new fields:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Test it out by once again reloading the fixtures. When we check the results,
both the ``created`` and ``updated`` columns are properly set. To avoid sadness
and regret add these fields to almost every table. 

Custom Query via a Repository
-----------------------------

Let's turn to something totally different. Right now, the homepage lists every
event in the order they were added to the database. We can do better! Head
to ``EventController`` and replace the ``findAll`` method with a custom query
that orders the events by the time property::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
    
        $entities = $em
            ->getRepository('EventBundle:Event')
            ->createQueryBuilder('e')
            ->addOrderBy('e.time', 'ASC')
            ->getQuery()
            ->execute()
        ;

        // ...
    }

Let's make the query a bit more complex by only showing upcoming events::

    $entities = $em
        ->getRepository('EventBundle:Event')
        ->createQueryBuilder('e')
        ->addOrderBy('e.time', 'ASC')
        ->andWhere('e.time > :now')
        ->setParameter('now', new \DateTime())
        ->getQuery()
        ->execute()
    ;

This uses the parameter syntax we saw before and uses a ``\DateTime`` object
to only show events after right now. We can test this out by tweaking our
fixtures and reloading them. When we refresh the page, the past event is missing.

Moving Queries to the Repository
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is great, but what if we want to reuse this query somewhere else? Instead
of keeping the query in the controller, create a new method inside ``EventRepository``
and move it there::

    // src/Yoda/EventBundle/Entity/EventRepository.php
    // ...

    public function getUpcomingEvents()
    {
        return $this
            ->createQueryBuilder('e')
            ->addOrderBy('e.time', 'ASC')
            ->andWhere('e.time > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute()
        ;
    }

Since we're now actually inside the repository, we just start by calling the
``createQueryBuilder()``. In the controller, continue to get the repository,
but now just call ``getUpcomingEvents`` to use the method::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em
            ->getRepository('EventBundle:Event')
            ->getUpcomingEvents()
        ;

        // ...
    }

.. note::

    The ``$em->getRepository('EventBundle:Event')`` returns our ``EventRepository``
    object.

This keeps all of our queries organized, makes them reusable, and makes our
controllers readable. We now have a "skinny" controller, which means that
we're doing a good job of organizing any logic we need in other classes. It
also means that you can show your code to fellow programmers and impress them
with your well-organized Jedi ways.

.. _`KnpBundles.com`: http://knpbundles.com/
.. _`go to its readme`: https://github.com/l3pp4rd/DoctrineExtensions/tree/master/doc
.. _`timestampable section of the documentation`: https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/timestampable.md
