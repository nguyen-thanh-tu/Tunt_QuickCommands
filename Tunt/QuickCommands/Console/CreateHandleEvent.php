<?php

namespace Tunt\QuickCommands\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateHandleEvent extends Command
{
    const MODULE = 'module';
    const EVENT_NAME = 'eventname';
    const OBSERVER_NAME = 'observername';
    const INSTANCE = 'instance';

    protected $dir;
    protected $module = '';
    protected $eventname = '';
    protected $observername = '';
    protected $instance = '';

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
                self::MODULE,
                null,
                InputOption::VALUE_REQUIRED,
                'Module'
            ),
            new InputOption(
                self::EVENT_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Event Name'
            ),
            new InputOption(
                self::OBSERVER_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Observer Name'
            ),
            new InputOption(
                self::INSTANCE,
                null,
                InputOption::VALUE_REQUIRED,
                'Instance'
            ),
        ];

        $this->setName('commands:create:handleevent')
            ->setDescription('Create Handle Event')
            ->setDefinition($options);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (
            ($this->module = $input->getOption(self::MODULE)) &&
            ($this->eventname = $input->getOption(self::EVENT_NAME)) &&
            ($this->observername = $input->getOption(self::OBSERVER_NAME)) &&
            ($this->instance = $input->getOption(self::INSTANCE))
        ) {
            $moduleName = str_replace('_','/',$this->module);
            $this->generatedModuleXML('/var/www/testmagento2/app/code/'.$moduleName);
        }
        return $this;
    }

    public function generatedModuleXML($paths)
    {
        $xml = <<<XML
    <event name="%s">
        <observer name="%s" instance="%s"/>
    </event>\n
XML;
        $xmlSnippet = sprintf($xml, $this->eventname, $this->observername, $this->instance);
        if(empty(is_file($paths.'/etc/events.xml')))
        {
            $registrationFile = fopen($paths.'/etc/events.xml', 'w');
            fwrite($registrationFile, '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
'.$xmlSnippet.'</config>');
            fclose($registrationFile);

            return $this;
        }
        $file = simplexml_load_file($paths.'/etc/events.xml');
        $events = (array)$file;
        $nodeAdd = null;
        foreach($events['event'] as $key => $event)
        {
            $event = (array)$event;
            if($event['@attributes']['name'] == $this->eventname)
            {
                $observer = (array)$event['observer'];
                if($observer['@attributes']['name'] == $this->observername || $observer['@attributes']['instance'] == $this->instance)
                {
                    return $this;
                }
                $nodeAdd = $key;
                $xmlSnippet = '    <observer name="'.$this->observername.'" instance="'.$this->instance.'"/>
    ';
                break;
            }
        }
        $dom = new \DOMDocument();
        $dom->loadXML($file->asXML());
        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($xmlSnippet);
        if(is_numeric($nodeAdd))
        {
            $dom->documentElement->getElementsByTagName('event')->item($nodeAdd)->appendChild($fragment);
        }else{
            $dom->documentElement->appendChild($fragment);
        }
        $dom->save($paths.'/etc/events.xml');

        return $this;
    }
}
