Refactoring to an External Form Type Class
------------------------------------------

Let's make one more change. Building a form right inside our controller is
simple and fun, but not easily reusable. Instead, let's move this logic to
an external class. Create a new ``Form`` directory and a new file called ``RegisterFormType``.
This is a form class. Give it the proper namespace and make it extend a class
called :symfonyclass:`Symfony\\Component\\Form\\AbstractType`::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    namespace Yoda\UserBundle\Form;

    use Symfony\Component\Form\AbstractType;

    class RegisterFormType extends AbstractType
    {
    }

A form class can have several methods. The first, but probably least important,
is :symfonymethod:`Symfony\\Component\\Form\\FormTypeInterface::getName`. This
should just return a string that's unique among your forms. It'll be used
in the name of the fields::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    public function getName()
    {
        return 'user_register';
    }

Next, the most important method is :symfonymethod:`Symfony\\Component\\Form\\FormTypeInterface::buildForm`.
When you create this method, don't forget the ``use`` statement for the
:symfonyclass:`Symfony\\Component\\Form\\FormBuilderInterface`. This
is where we build our fields, and we can basically copy in the code form our
controller and put it here::

    // src/Yoda/UserBundle/Form/RegisterFormType.php

    use Symfony\Component\Form\FormBuilderInterface;
    // ...

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text')
            ->add('email', 'email')
            ->add('plainPassword', 'repeated', array(
                'type' => 'password'
            )
        );
    }

Finally, create a ``setDefaultOptions`` function. Use this to return an array
with the ``data_class`` option::

    // src/Yoda/UserBundle/Form/RegisterFormType.php

    use Symfony\Component\OptionsResolver\OptionsResolverInterface;
    // ...

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yoda\UserBundle\Entity\User',
        ));
    }

And that's it! Remove the form code in our controller. We can replace it
by calling ``createForm`` and passing it an instance of our new ``RegisterFormType``::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    
    use Yoda\UserBundle\Form\RegisterFormType;
    // ...

    public function registerAction(Request $request)
    {
        $defaultUser = new User();
        $defaultUser->setUsername('Foo');

        $form = $this->createForm(new RegisterFormType(), $defaultUser);

        // ...
    }

Let's refresh the page to make sure that everything went ok. Great!

Forms: From Space
-----------------

Let's review. A form is something you build, giving it the fields and field
types you need. By default, a form just returns an array of data, but we can
change that with the ``data_class`` option to return a populated object. Forms
can also be built right inside the controller or in an external class. Got
it? Great! Let's move on to validation.