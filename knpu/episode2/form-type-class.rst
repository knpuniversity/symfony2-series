Using an External Form Type Class
=================================

We built our form right inside the controller, which was really simple. But,
it makes our controller a bit ugly and if we needed to re-use this form somewhere
else, that wouldn't be possible.

For those reasons, the code to create a form *usually* lives in its own class.
Create a new ``Form`` directory and a new file called ``RegisterFormType``.
Create the class, give it a namespace and make it extend a class called
:symfonyclass:`Symfony\\Component\\Form\\AbstractType`::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    namespace Yoda\UserBundle\Form;

    use Symfony\Component\Form\AbstractType;

    class RegisterFormType extends AbstractType
    {
    }

We need to add a few methods to this class. The first, and least important
is :symfonymethod:`Symfony\\Component\\Form\\FormTypeInterface::getName`.
Add this, and just return a string that's unique among your forms. It's
used as part of the ``name`` attribute on your rendered form::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    public function getName()
    {
        return 'user_register';
    }

The really important method is :symfonymethod:`Symfony\\Component\\Form\\FormTypeInterface::buildForm`.
I'm going to use my IDE to create this method for me. If you create your's
manually, just don't forget the use statement for the
:symfonyclass:`Symfony\\Component\\Form\\FormBuilderInterface`.

The ``buildForm`` method is where we build our form! Genius! Copy the code
from our controller that adds the fields and put that here::

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

Finally, create a ``setDefaultOptions`` function and set the ``data_class``
option on it::

    // src/Yoda/UserBundle/Form/RegisterFormType.php

    use Symfony\Component\OptionsResolver\OptionsResolverInterface;
    // ...

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yoda\UserBundle\Entity\User',
        ));
    }

That's it! This class is now a recipe for exactly how the form should look.

Using the Form Class
--------------------

Remove the builder code in our controller. Instead, replace it with a call to
``createForm`` and pass it an instance of the new ``RegisterFormType``::

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

Refresh! We've conquered forms!

Forms: From Space
-----------------

Some quick review. A form is something you build, giving it the fields and
field types you need. At first, a form just returns an associative array,
but we can change that with the ``data_class`` option to return a populated
object. Forms can also be built right inside the controller or in an external
class.

Got it? Great! Let's move on to validation.
