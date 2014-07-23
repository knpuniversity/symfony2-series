More about Container, the "doctrine" Service and the Entity Manager
===================================================================

In our test, we needed Doctrine's entity manager and to get it, we used
Symfony's container. Remember from the `first episode in this series`_
that the "container" is basically just a big array filled with useful objects.
To get a list of all of the objects in the container, run the ``container:debug``
console command:

.. code-block:: bash

    php app/console container:debug

One of these objects is called ``doctrine``, which is an instance of a class
called ``Registry``:

.. code-block:: text

    ...
    doctrine    container    Doctrine\Bundle\DoctrineBundle\Registry

So when we say ``$container->get('doctrine')``, we're getting this object.

Find the shortcut in your editor that can open files by typing in their
filename. Use this to find and open ``Registry.php``. Inside, you'll see
the ``getManager`` method being used, which actually lives on its parent
class. 

The Base Controller
-------------------

So how does this compare with how we normally get the entity manager in a
controller? Open up ``RegisterController``. That's right - in a controller,
we always say ``$this->getDoctrine()->getManager()``::

    $em = $this->getDoctrine()->getManager();

The ``getDoctrine()`` method lives inside Symfony's ``Controller`` class,
which we're extending. Let's open up that class to see what this method does::

    // vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
    public function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

Ah-hah! The ``getDoctrine`` method is just a shortcut to get the service
object called ``doctrine`` from the container. This means that no matter
where we are, the process to get the entity manager is always the same: get
the container, get the ``doctrine`` service, then call ``getManager`` on it.

My big point is that if you have the container, then you can get *any* object
in order to do *any* work you need to. The only tricky part is knowing what
the name of the service object is and what methods you can call on it. Using
the ``app/console container:debug`` task can help.

.. _`first episode in this series`: http://knpuniversity.com/screencast/symfony2-ep1/controller#symfony-ep1-what-is-a-service
