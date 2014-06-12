Doctrine's QueryBuilder
=======================

What if we wanted to find a ``User`` by matching on the ``email`` *or* ``username``
columns? We would of course add a ``findOneByUsernameOrEmail`` method to
``UserRepository``::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    class UserRepository extends EntityRepository
    {
        public function findOneByUsernameOrEmail()
        {
            // ... todo - get your query on
        }
    }

To make queries, you can use an SQL-like syntax called DQL, for Doctrine
query language. You can even use native SQL queries if you're doing something
really complex.

But most of the time, I recommend using the awesome query builder object.
To get one, call ``createQueryBuilder`` and pass it an "alias". Now, add
the where clause with our ``OR`` logic::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    public function findOneByUsernameOrEmail($username)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

The query builder has every method you'd expect, like ``leftJoin``, ``orderBy``
and ``groupBy``. It's really handy.

The stuff inside ``andWhere`` looks similar to SQL except that we use "placeholders"
for the two variables. Fill each of these in by calling ``setParameter``.
The reason this is separated into two steps is to avoid `SQL injection attacks`_,
which are really no fun.

To finish the query, call ``getQuery`` and then ``getOneOrNullResult``, which,
as the name sounds, will return the ``User`` object if it's found or null if it's
not found.

.. note::

    To learn more about the Query Builder, see `doctrine-project.org: The QueryBuilder`.

To try this out, let's temporarily reuse the EventController's ``indexAction``.
Get the ``UserRepository`` by calling ``getRepository`` on the entity manager.
Remember, the argument you pass to ``getRepository`` is the entity's
"shortcut name": the bundle name followed by the entity name::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        // temporarily abuse this controller to see if this all works
        $userRepo = $em->getRepository('UserBundle:User');

        // ...
    }

Now that we have the UserRepository, let's try our new method and dump the result::

    public function indexAction()
    {
        // ...
        $userRepo = $em->getRepository('UserBundle:User');
        var_dump($userRepo->findOneByUsernameOrEmail('user'));die;

        // ...
    }

When we refresh, we see the user. If we try the email instead, we get
the same result::

    var_dump($userRepo->findOneByUsernameOrEmail('user@user.com'));die;

Cool! Now let's get rid of these debug lines - I'm trying to get a working
project going here people!

But this is a really common pattern we'll see more of: use the repository
in a controller to fetch objects from the database. If you need a special
query, just add a new method to your repository and use it.

.. _`SQL injection attacks`: http://xkcd.com/327/
.. _`doctrine-project.org: The QueryBuilder`: http://bit.ly/d2-query-builder
