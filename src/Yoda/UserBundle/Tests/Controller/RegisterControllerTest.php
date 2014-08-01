<?php

namespace Yoda\EventBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterControllerTest extends WebTestCase
{
    public function testRegister()
    {
        $client = static::createClient();

        $container = self::$kernel->getContainer();
        $em = $container->get('doctrine');
        $userRepo = $em->getRepository('UserBundle:User');
        $userRepo->createQueryBuilder('u')
            ->delete()
            ->getQuery()
            ->execute()
        ;

        $crawler = $client->request('GET', '/register');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Register', $response->getContent());

        $usernameVal = $crawler
            ->filter('#user_register_username')
            ->attr('value')
        ;
        $this->assertEquals('Leia', $usernameVal);

        // the name of our button is "Register!"
        $form = $crawler->selectButton('Register!')->form();

        $crawler = $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegexp(
            '/This value should not be blank/',
            $client->getResponse()->getContent()
        );

        // submit the form again
        $form = $crawler->selectButton('Register!')->form();

        $form['user_register[username]'] = 'user5';
        $form['user_register[email]'] = 'user5@user.com';
        $form['user_register[plainPassword][first]'] = 'P3ssword';
        $form['user_register[plainPassword][second]'] = 'P3ssword';

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertContains(
            'Registration went super smooth',
            $client->getResponse()->getContent()
        );
    }
}
