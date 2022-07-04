<?php

namespace Tunt\QuickCommands\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class CreatePlugin extends Command
{
    const CLASS_NAME = 'classname';
    const PLUGIN_NAME = 'pluginname';
    const PLUGIN_ClASS = 'pluginclass';

    protected $className;
    protected $pluginName;
    protected $pluginClass;

    public function __construct
    (
        \Magento\Framework\Filesystem\DirectoryList $dir,
        string $name = null
    )
    {
        $this->dir = $dir;
        parent::__construct($name);
    }

    protected function configure()
    {
        $options = [
            new InputOption(
                self::CLASS_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Class Name'
            ),
            new InputOption(
                self::PLUGIN_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Plugin Name'
            ),
            new InputOption(
                self::PLUGIN_ClASS,
                null,
                InputOption::VALUE_REQUIRED,
                'Plugin Class'
            )
        ];

        $this->setName('commands:create:plugin')
            ->setDescription('Create Plugin')
            ->setDefinition($options);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (
            ($this->className = $input->getOption(self::CLASS_NAME)) &&
            ($this->pluginName = $input->getOption(self::PLUGIN_NAME)) &&
            ($this->pluginClass = $input->getOption(self::PLUGIN_ClASS))
        )
        {
            $this->generatedDiXML();
        }
        return $this;
    }

    public function generatedDiXML()
    {
        $path = '/var/www/testmagento2/app/code/Tunt/Test/etc/di.xml';
        if(empty(is_file($path)))
        {
            $registrationFile = fopen($path, 'w');
            fwrite($registrationFile, $this->getDiXMLContent());
            fclose($registrationFile);
        }else{
            $file = simplexml_load_file($path);
            $events = (array)$file;
        }
        return $this;
    }

    public function getDiXMLContent()
    {
        $content = '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="'.$this->className.'">
        <plugin name="'.$this->pluginName.'" type="'.$this->pluginClass.'" />
    </type>
</config>
';
        return $content;
    }

}
