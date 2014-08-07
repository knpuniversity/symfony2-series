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
library and adds some Symfony glue to make things really easy. Click into
its documentation.

Installing a bundle is always the same 3 steps. First, use Composer's ``require``
command and pass it the name of the library:

.. code-block:: bash

    php composer.phar require stof/doctrine-extensions-bundle

If it asks you for a version, type `~1.1.0`. In the future, Composer should
decide the best version for you.

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

All of the details on how to install a bundle and configure it will always
live in its documentation.

Adding Sluggable to Event
-------------------------

This bundle brings in a bunch of cool features, which we have to activate
manually in ``config.yml``. The first is called "sluggable":

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

This is just a normal property that will store a URL-safe and unique version
of the event's name. And now let's add the getter and setter::

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

Configuring slug to be set Automatically
----------------------------------------

Ready for the magic? Let's see if we can get the ``slug`` field to be automatically
populated for us, based on the event's name.

The ``StofDoctrineExtensionBundle`` is actually just a wrapper around another
library called ``DoctrineExtensions`` that does most of the work. We can
`go to its README`_ to get real usage details. Find the ``sluggable`` section
and look at the first example.

This library works via annotations, so copy and paste the new ``use`` statement
into ``Event``. Next, copy the annotation from the slug field and change the
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

This says that we want DoctrineExtensions to automatically set the ``slug``
field based on the ``name`` property. If we also set ``updatable`` to ``false``,
it tells the library to set ``slug`` once and never change it again, even
if the event's name changes. That's good because the slug will be used in
the event's URL. And changing URLs is lame :).

Let's try it! Update the database schema:

.. code-block:: bash

    php app/console doctrine:schema:update --force

This explodes because our existing events will all temporarily have blank
slugs, which isn't unique. Drop the schema and rebuild from scratch to get
around this:

.. code-block:: bash

    php app/console doctrine:schema:drop --force
    php app/console doctrine:schema:create
    php app/console doctrine:fixtures:load

Reload the fixtures and check the results by querying for events via the console:

.. code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM yoda_event"

Hey, we have slugs! That's not something you would be excited about outside of programming.
As an added bonus, if two events have the same name, the library
will automatically add a ``-1`` to the end of the second slug. The
library has our back and makes sure that these are always unique.

.. _`KnpBundles.com`: http://knpbundles.com/
.. _`go to its readme`: https://github.com/Atlantic18/DoctrineExtensions/tree/master/doc
.. _`DoctrineExtensions`: https://github.com/Atlantic18/DoctrineExtensions
.. _`StofDoctrineExtensionsBundle`: https://github.com/stof/StofDoctrineExtensionsBundle
