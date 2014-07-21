Adding createdAt and updatdAt Timestampable Fields
==================================================

Let's do more magic! I alwas like to have ``createdAt`` and ``updatedAt``
fields on my database tables. A lot of times, this helps me debug any weird
behavior I may see in the future.

The DoctrineExtensions library can does this for is. It's called ``timestampable``,
enable it in ``config.yml``:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    stof_doctrine_extensions:
        orm:
            default:
                sluggable: true
                timestampable: true

Head to the `timestampable section of the documentation`_ to see how this works.
We already have the ``Gedmo`` annotation, so just copy in the ``created`` and
``updated`` properties and rename them to ``createdAt`` and ``updatedAt``,
just because I like those names better::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

Generate getter methods for these::

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

We can also add setter methods if we want, but we don't need them: the library
will set these for us!

Next, update the database schema to add the two new fields and the reload
the fixtures:

.. code-block:: bash

    php app/console doctrine:schema:update --force
    php app/console doctrine:fixtures:load

Query for the events again:

.. code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM yoda_event"

Nice! Both the ``createdAt`` and ``updatedAt`` columns are properly set.
To avoid sadness and regret add these fields to almost every table.

.. _`timestampable section of the documentation`: https://github.com/Atlantic18/DoctrineExtensions/blob/master/doc/timestampable.md
