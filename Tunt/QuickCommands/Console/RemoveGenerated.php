<?php

namespace Tunt\QuickCommands\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class RemoveGenerated extends Command
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

        $this->setName('commands:delete:generated')
            ->setDescription('Delete Generated Folder')
            ->setDefinition($options);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($name = $input->getOption(self::NAME)) {
            $paths = $this->dir->getPath('generated').'/'.$name;
            $this->removeDirectory($paths);
            $output->writeln("Delete generated ".$name." folder successful");
        } else {
            $paths = $this->dir->getPath('generated');
            $this->removeDirectory($paths);
            $output->writeln("Delete generated folder successful");
        }
        return $this;
    }

    function removeDirectory($paths) {

        $files = glob($paths . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        if($paths != $this->dir->getPath('generated'))
        {
            rmdir($paths);
        }
    }
}
