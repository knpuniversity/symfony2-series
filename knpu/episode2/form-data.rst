Form: Default Data
------------------

Back on the registration form, what if we wanted some default data to appear? 
We can just pass the data as the first argument when we create the form::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    public function registerAction(Request $request)
    {
        $defaultData = array(
            'username' => 'Foo',
        );

        $form = $this->createFormBuilder($defaultData)
            // ...
            ->getForm()
        ;

        // ...
    }

Having the Form to a User object: The data_class Option
--------------------------------------------------------

Having a form that returns an array of data is great, but what if we could
make a form that automatically built the ``User`` object for us? Clear out
the default data we just added and add a second array. This is an array of options
that we're passing into the form. By passing it a ``data_class`` option,
our bound form will return a ``User`` object instead of an array::

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

    // inside registerAction()
    if ($form->isValid()) {
        var_dump($form->getData());die;

        // all the User saving code from before ...
    }

Great! As expected, we get back a full ``User`` object populated with the
data. Behind the scenes, the form component creates a new ``User`` object
and then calls ``setUsername``, ``setEmail`` and ``setPassword`` on it, passing
each the value from the form. We can now simplify things on our controller::

    // inside registerAction()
    if ($form->isValid()) {
        $user = $form->getData();

        $user->setPassword(
            $this->encodePassword($user, $user->getPassword())
        );

        $em = $this->getDoctrine()->getManager();
        // save the user and redirect just as before
    }

But what about default data? Let's put back the array we had earlier::

    $defaultData = array(
        'username' => 'Foo',
    );

    $form = $this->createFormBuilder($defaultData, array(
        'data_class' => 'Yoda\UserBundle\Entity\User',
    ))
        // ...
        ->getForm()
    ;

Look at the error message closely:

>
The form's view data is expected to be an instance of class Yoda\UserBundle\Entity\User,
but is a(n) array. You can avoid this error by setting the "data_class" option
to null or by adding a view transformer that transforms a(n) array to an
instance of Yoda\UserBundle\Entity\User.

It's telling us that we gave it an array but it was expecting a ``User`` object.
By using the ``data_class`` option, we're telling the form that both the
output *and* the input of the form should be a ``User``. To set default data,
just create a ``User`` object, give it some data and pass it in::

    $defaultUser = new User();
    $defaultUser->setUsername('Foo');

    $form = $this->createFormBuilder($defaultUser, array(
        'data_class' => 'Yoda\UserBundle\Entity\User',
    ))
        // ...
        ->getForm()
    ;
