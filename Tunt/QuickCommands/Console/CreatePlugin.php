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
            $pathAppCode = $this->dir->getPath('app').'/code';
            $this->generatedDiXML();
            $this->generatedPluginPHP($pathAppCode);
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
            $xml = <<<XML
    <type name="%s">
        <plugin name="%s" type="%s" />
    </type>\n
XML;
            $xmlSnippet = sprintf($xml, $this->className, $this->pluginName, $this->pluginClass);
            $file = simplexml_load_file($path);
            $events = (array)$file;
            $dom = new \DOMDocument();
            $dom->loadXML($file->asXML());
            $fragment = $dom->createDocumentFragment();
            $norender = false;
            if(!empty($events['type']))
            {
                if(
                    !empty(((array)$events['type'])['@attributes']) &&
                    !empty(((array)$events['type'])['plugin'])
                )
                {
                    $events['type'] = [
                        0 => [
                            '@attributes' => ((array)$events['type'])['@attributes'],
                            'plugin'      => ((array)$events['type'])['plugin']
                            ]
                    ];
                }
                foreach($events['type'] as $key => $type)
                {
                    $type = (array)$type;
                    if(
                        !empty($type['plugin']) &&
                        $type['@attributes']['name'] == $this->className &&
                        ((array)$type['plugin'])['@attributes']['type'] == $this->pluginClass
                    )
                    {
                        $norender = true;
                        break;
                    }
                }
            }
            if(!$norender)
            {
                $fragment->appendXML($xmlSnippet);
                $dom->documentElement->appendChild($fragment);
                $dom->save($path);
            }
        }
        return $this;
    }

    public function generatedPluginPHP($paths)
    {
        $folders = explode('\\',$this->pluginClass);
        $namespace = '';
        foreach($folders as $key => $folder)
        {
            $paths .= '/'.$folder;
            if(empty(is_file($paths.'.php')) && $key == (count($folders) - 1))
            {
                $namespace = rtrim($namespace,'\\');
                $class = $folder;
                $registrationFile = fopen($paths.'.php', 'w');
                fwrite($registrationFile, $this->getContentPluginPHP($namespace,$class));
                fclose($registrationFile);
                break;
            }elseif(empty(is_dir($paths)) && $key != (count($folders) - 1))
            {
                mkdir($paths);
            }
            $namespace .= $folder.'\\';
        }
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

    public function getContentPluginPHP($namespace,$class)
    {
        $functions = '';
        $param = '';
        $reflect = new \ReflectionClass($this->className);
        if(!$reflect->isFinal()){
            $interfaces = class_implements($this->className);
            if(empty($interfaces['Magento\Framework\ObjectManager\NoninterceptableInterface']))
            {
                $classMethods = get_class_methods($this->className);
                foreach($classMethods as $method)
                {
                    if(in_array($method,['__construct','__destruct']))
                    {
                        continue;
                    }
                    $classMethod = new \ReflectionMethod($this->className,$method);
                    if($classMethod->isPublic())
                    {
                        if($classMethod->isStatic() || $classMethod->isFinal()){
                            continue;
                        }
                        $paramaters = $classMethod->getParameters();
                        foreach($paramaters as $paramater)
                        {
                            $param .= ', $'.$paramater->name;
                        }
                        $functions .= '
    // plugin '.$method.'
    public function before'.ucfirst($method).'('.'\\'.$this->className.' $subject'.$param.')
    {
        return ['.ltrim($param,', ').'];
    }

    public function around'.ucfirst($method).'('.'\\'.$this->className.' $subject, callable $proceed'.$param.')
    {
    }

    public function after'.ucfirst($method).'('.'\\'.$this->className.' $subject, $result)
    {
        return $result;
    }
    ';
                        $param = '';
                    }
                }
            }
        }

        $content = '<?php

namespace '.$namespace.';

class '.$class.'
{
    '.$functions.'
}';
        return $content;
    }
}

