<?php

namespace Tunt\QuickCommands\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateController extends Command
{
    const MODULE = 'module';
    const CONTROLLER = 'controller';

    protected $dir;
    protected $namespace;
    protected $class;

    public function __construct
    (
        \Magento\Framework\Filesystem\DirectoryList $dir,
        string $name = null
    )
    {
        $this->dir = $dir;
        $this->namespace = [];
        $this->class = '';
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
                self::CONTROLLER,
                null,
                InputOption::VALUE_REQUIRED,
                'Controller'
            )
        ];

        $this->setName('commands:create:controller')
            ->setDescription('Create Controller')
            ->setDefinition($options);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (($module = $input->getOption(self::MODULE)) && ($controller = $input->getOption(self::CONTROLLER))) {
            $paths = $this->dir->getPath('app').'/code';
            $moduleName = explode('_',$module);
            foreach($moduleName as $folder)
            {
                $paths .= '/'.$folder;
                if(empty(is_dir($paths)))
                {
                    mkdir($paths);
                }
                $this->namespace[] = $folder;
            }
            if(empty(is_dir($paths.'/Controller')))
            {
                mkdir($paths.'/Controller');
            }
            $this->namespace[] = 'Controller';
            $this->generatedController($paths.'/Controller',$controller);
            $output->writeln("Created controller successful");
        } else {
            $output->writeln("Not Create");
        }
        return $this;
    }

    public function generatedController($paths,$controller)
    {
        $ctrl = explode('/',$controller);
        foreach($ctrl as $key => $folderOrFile)
        {
            $paths .= '/'.$folderOrFile;
            if($folderOrFile == end($ctrl) && $key == (count($ctrl)-1))
            {
                $this->class = $folderOrFile;
                $this->generatedControllerPHP($paths.'.php');
                break;
            }else{
                if(empty(is_dir($paths)))
                {
                    mkdir($paths);
                }
            }
            $this->namespace[] = $folderOrFile;
        }
    }

    public function generatedControllerPHP($paths)
    {
        if(empty(is_file($paths)))
        {
            $registrationFile = fopen($paths, 'w');
            fwrite($registrationFile, $this->controllerPHPContent());
            fclose($registrationFile);
        }
    }

    public function controllerPHPContent()
    {
        $extendsClass = '\Magento\Framework\App\Action\Action';
        if ($this->namespace[3] == 'Adminhtml'){
            $extendsClass = '\Magento\Backend\App\Action';
        }
        $namespace = implode('\\',$this->namespace);
        $content = "<?php\n
namespace ".$namespace.";

class ".$this->class." extends ".$extendsClass."
{
    public function execute()
    {
        // TODO: Implement execute() method.
    }
}";
        return $content;
    }
}
