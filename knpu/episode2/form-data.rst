Form: Default Data
------------------

Now, what if we wanted some default data to appear on the form? Well, we
can just pass the data as the first argument to ``createFormBuilder``::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    public function registerAction(Request $request)
    {
        $defaultData = array(
            'username' => 'Leia',
        );

        $form = $this->createFormBuilder($defaultData)
            // ...
            ->getForm()
        ;

        // ...
    }

Refresh and check that out.

Having the Form to a User object: The data_class Option
--------------------------------------------------------

When we submit, ``$form->getData()`` gives us an associative array. That's
cool, but what if it actually built the ``User`` object for us? Remove the
default data we just added and pass a second argument to ``createFormBuilder``.
This is an array of options for the form and we'll pass it a ``data_class``
key that's set to our ``User`` class::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    public function registerAction(Request $request)
    {
        $form = $this->createFormBuilder(null, array(
            'data_class' => 'Yoda\UserBundle\Entity\User',
        ))
            // ...
            ->getForm()
        ;

        // ...
    }

Let's dump the form values again and try it::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    if ($form->isValid()) {
        $data = $form->getData();
        var_dump($data);die;

        // all the User saving code from before ...
    }

Cool! Instead of an associative array, we get back a full ``User`` object
populated with the form data. Behind the scenes, the form creates a new ``User``
object and then calls ``setUsername``, ``setEmail`` and ``setPassword`` on
it, passing each the value from the form.

Now, We can simplify things on our controller::

    // inside registerAction()
    if ($form->isValid()) {
        $user = $form->getData();

        $user->setPassword(
            $this->encodePassword($user, $user->getPassword())
        );

        $em = $this->getDoctrine()->getManager();
        // save the user and redirect just as before
    }

Default Data with an Object
---------------------------

So how can we set default data on the form now? Put back the array we had
earlier::

    $defaultData = array(
        'username' => 'Leia',
    );

    $form = $this->createFormBuilder($defaultData, array(
        'data_class' => 'Yoda\UserBundle\Entity\User',
    ))
        // ...
        ->getForm()
    ;

Refresh and look at the error message closely:

.. code-block:: text

    The form's view data is expected to be an instance of class
    Yoda\UserBundle\Entity\User, but is a(n) array. You can avoid
    this error by setting the "data_class" option to null or by adding
    a view transformer that transforms a(n) array to an instance
    of Yoda\UserBundle\Entity\User.

It's telling us that we gave the form an array but it was expecting a ``User``
object. The ``data_class`` option tells the form that both the output *and*
the input of the form should be a ``User``. So to set default data, just
create a ``User`` object, give it some data and pass it in::

    $user = new User();
    $user->setUsername('Leia');

    $form = $this->createFormBuilder($user, array(
        'data_class' => 'Yoda\UserBundle\Entity\User',
    ))
        // ...
        ->getForm()
    ;

Refresh now! It looks great!
