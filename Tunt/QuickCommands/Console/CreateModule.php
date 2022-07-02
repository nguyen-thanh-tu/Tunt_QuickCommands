<?php

namespace Tunt\QuickCommands\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateModule extends Command
{
    const NAME = 'name';

    protected $dir;

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
                self::NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Name'
            )
        ];

        $this->setName('commands:create:module')
            ->setDescription('Create Module')
            ->setDefinition($options);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($name = $input->getOption(self::NAME)) {
            $paths = $this->dir->getPath('app').'/code';
            $moduleName = explode('_',$name);
            foreach($moduleName as $folder)
            {
                $paths .= '/'.$folder;
                if(empty(is_dir($paths)))
                {
                    mkdir($paths);
                }
            }
            if(empty(is_dir($paths.'/etc')))
            {
                $this->generatedModuleXML($paths,$name);
            }
            if(empty(is_file($paths.'/registration.php')))
            {
                $this->generatedRegistrationPHP($paths,$name);
            }
            $output->writeln("Created ".$name." module successful");
        } else {
            $output->writeln("Not Create");
        }
        return $this;
    }

    public function generatedModuleXML($paths,$name)
    {
        mkdir($paths.'/etc');
        $moduleFile = fopen($paths.'/etc/module.xml', 'a');
        fwrite($moduleFile, $this->moduleXMLContent($name));
        fclose($moduleFile);
    }

    public function generatedRegistrationPHP($paths,$name)
    {
        $registrationFile = fopen($paths.'/registration.php', 'w');
        fwrite($registrationFile, $this->registrationPHPContent($name));
        fclose($registrationFile);
    }

    public function moduleXMLContent($name)
    {
        $content = '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="'.$name.'" setup_version="1.0.0"/>
</config>';
        return $content;
    }

    public function registrationPHPContent($name)
    {
        $content = "<?php\n\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    '".$name."',
    __DIR__
);";
        return $content;
    }
}
