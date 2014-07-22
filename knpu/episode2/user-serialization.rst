User Serialization
==================

There's a problem 

I have to bother you *quickly* with a little issue of serialization. When a
user logs in, the ``User`` entity is stored in the session. For this to work,
PHP serializes the User object to a string at the end of the request and
stores it. At the beginning of the request, that string is unserialized and
turned back into the User object.

.. note::

    If you're feeling really curious, the class that serializes and deserializes
    the user information is called ``ContextListener``.

This is great! And it's obviously working great - we're surfing around as
Wayne the admin. But there's a "gotcha" in Doctrine. Sometimes, Doctrine will stick
some extra information onto our entity, like the entity manager: that big
important object we used to save things.

Normally, we don't care about this, but when the User object is serialized,
having that big object hidden in our entity causes serialization to fail.
The entity manager contains a database connection and other information that
just *can't* be serialized.

Using the Serializable Interface
--------------------------------

We need to help Doctrine out. Start by adding the :phpclass:`Serializable`
interface to the User class. This core PHP interface has two methods:
``serialize`` and ``unserialize``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    use Serializable;

    class User implements AdvancedUserInterface, Serializable
    {
        // ...

        public function serialize()
        {
            // todo - do some mad serialization
        }

        public function unserialize($serialized)
        {
            // todo - and some equally angry de-serialization
        }
    }

When the ``User`` object is serialized, it'll call the ``serialize`` method
instead of trying to do it automatically. When the string is deserialized,
the ``unserialize`` method is called. This may seem odd, but let's just return
the ``id``, ``username`` and ``password`` inside an array for ``serialize``.
For ``unserialize``, just put those 3 values back on the object::

    // src/Yoda/UserBundle/Entity/User.php
    // ..

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
        ));
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
        ) = unserialize($serialized);
    }

If you think about it, this should kinda break everything. When Symfony
gets the ``User`` object from the session and deserializes it, our User
will have lost some of its data, like ``roles`` and ``isActive``. That's
not cool!

Clearly that's not the case: Symfony's security system is smart enough to
take the ``id`` and query for a full fresh copy of the User object on each
request.

We can see this right in the web debug toolbar: once a user is logged in,
each request has a query that grabs the current user from the database.
So, we're good!
