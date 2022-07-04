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
    const AREA = 'area';

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
                self::AREA,
                null,
                InputOption::VALUE_REQUIRED,
                'Area'
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
            $pathAppCode = $this->dir->getPath('app').'/code';
            $area = $input->getOption(self::AREA).'/';
            $moduleName = str_replace('_','/',$this->module);
            $pathEtc = $pathAppCode.'/'.$moduleName.'/etc/';
            if(empty(is_dir($pathEtc.$input->getOption(self::AREA))))
            {
                mkdir($pathEtc.$input->getOption(self::AREA));
            }
            $this->generatedModuleXML($pathEtc.$area.'events.xml');
            $this->generatedObserverPHP($pathAppCode);
        }
        return $this;
    }

    public function generatedModuleXML($path)
    {
        $xml = <<<XML
    <event name="%s">
        <observer name="%s" instance="%s"/>
    </event>\n
XML;
        $xmlSnippet = sprintf($xml, $this->eventname, $this->observername, $this->instance);
        if(empty(is_file($path)))
        {
            $registrationFile = fopen($path, 'w');
            fwrite($registrationFile, '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
'.$xmlSnippet.'</config>');
            fclose($registrationFile);

            return $this;
        }
        $file = simplexml_load_file($path);
        $events = (array)$file;
        $eventss = (array)$events['event'];
        if(!empty($eventss['@attributes']) || !empty($eventss['observer']))
        {
            $events = [];
            $events[0] =
                [
                    '@attributes' => $eventss['@attributes'],
                    'observer' => $eventss['observer']
                ];
        }else{
            $events = $eventss;
        }
        $nodeAdd = null;
        foreach($events as $key => $event)
        {
            $event = (array)$event;
            if($event['@attributes']['name'] == $this->eventname)
            {
                $observerss = (array)$event['observer'];
                if(!empty($observerss['@attributes']))
                {
                    $observers = [];
                    $observers[0] = [
                        '@attributes' => $observerss['@attributes']
                    ];
                }else{
                    $observers = $observerss;
                }
                foreach($observers as $observer){
                    $observer = (array)$observer;
                    if($observer['@attributes']['name'] == $this->observername || $observer['@attributes']['instance'] == $this->instance)
                    {
                        return $this;
                    }
                }
                $nodeAdd = (int)$key;
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
        $dom->save($path);

        return $this;
    }

    public function generatedObserverPHP($paths)
    {
        $folders = explode('\\',$this->instance);
        $namespace = '';
        foreach($folders as $key => $folder)
        {
            $paths .= '/'.$folder;
            if(empty(is_file($paths)) && $key == (count($folders) - 1))
            {
                $namespace = rtrim($namespace,'\\');
                $class = $folder;
                $registrationFile = fopen($paths.'.php', 'w');
                fwrite($registrationFile, $this->getContentObserverPHP($namespace,$class));
                fclose($registrationFile);
                break;
            }elseif(empty(is_dir($paths)))
            {
                mkdir($paths);
            }
            $namespace .= $folder.'\\';
        }
    }

    public function getContentObserverPHP($namespace,$class)
    {
        $content = '<?php

namespace '.$namespace.';

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class '.$class.' implements ObserverInterface
{
    public function execute(Observer $observer)
    {

    }
}';
        return $content;
    }
}
