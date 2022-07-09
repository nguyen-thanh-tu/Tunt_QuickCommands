# Tunt_QuickCommands
Quickly create modules, controllers, plugins, events with just one command line

1: Create Module

bin/magento commands:create:module --name="Your_Module"

2: Delete Generated Folder

bin/magento commands:delete:generated

3: Create Controller 

bin/magentp commands:create:controller --module="Your_Module" --controller="Your\Module\Controller\ControllerName\ActionName"

4 Create Observer

bin/magentp commands:create:handleevent --module="Your_Module" --eventname"eventname_to_catch" --observername="your_observername" --instance="Your\Module\Observer\Event"
