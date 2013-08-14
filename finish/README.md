Getting Started with Symfony2
=============================

This code represents the final product of the screencast. To get things working,
try the following steps (or try to remember what we learned in the video and figure
it out on your own!):

1) Update your vendors via Composer

    php composer.phar install

2) Copy your parameters.yml.dist file to parameters.yml and customize it

    cp app/config/parameters.yml.dist app/config/parameters.yml

3) Fix your permissions

    chmod -R 777 app/cache app/logs

4) Build your database

    php app/console doctrine:database:create
    php app/console doctrine:schema:create
    php app/console doctrine:fixtures:load

5) Setup a virtualhost that points to the web/ directory and a hosts entry
   for your fake domain

6) Pop it open in your browser!

