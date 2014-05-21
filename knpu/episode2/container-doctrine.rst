More about Container, the "doctrine" Service and the Entity Manager
===================================================================

Quickly, let's talk about how we used the container at the top of the class.
Remember from the `first episode in this series`_ that the "container" in
Symfony is basically just a big array filled with useful objects. To get a
list of all of the objects in the container, run the ``container:debug`` console
command:

.. code-block:: bash

    php app/console container:debug

We can see that there's an object called ``doctrine`` and that it's a class
called Registry:

.. code-block:: text

    ...
    doctrine            container       Doctrine\Bundle\DoctrineBundle\Registry

If your editor can open files by class or filename, then you can open this
quickly to look inside. When you do, you'll see the ``getManager`` method.

Look now at how we normally get the entity manager from a controller by calling
the ``getDoctrine`` method::

    $em = $this->getDoctrine()->getManager();

Now that we know a little bit more about the container, let's open up the
base controller class to see what this method does::

    // vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
    public function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

Sweet! The ``getDoctrine`` method is just a shortcut to get out the service
called ``doctrine``. No matter where we are, the process to get the entity
manager is always the same: get the container and then find the service you
need. Life is easy from inside a controller or a functional test because we
have the container at our fingertips. Of course, sometimes you won't have
access to the container, but we'll cover that later.
