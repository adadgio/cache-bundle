<?php

namespace Adadgio\CacheBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;

// @todo Move inside adadgio cache bundle
class FileCacheCommand extends ContainerAwareCommand
{
    /**
     * @var object
     */
    private $container;

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('adadgio:cache:clear')
            ->setDescription("Clear custom adadgio cache")
            ->addOption(
                'cat',
                null,
                InputOption::VALUE_REQUIRED,
                'Custom cache folder (category) to purge',
                null
            )
        ;
    }

    /**
     * Execute command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getKernel()->getContainer();
        $cache = $container->get('adadgio_cache.file_cache');

        $category = null;

        if (null !== $input->getOption('cat')) {
            $cat = ($input->getOption('cat') === '*') ? null : $input->getOption('cat');
            $cache->identify($cat);
        } else {
            $cache->identify(null);
            $output->writeln(sprintf('Clearing every custom cache directory is forbidden for security measures in "%s". You can force with --cat=* option', basename($cache->getCacheDir())));
            return;
        }

        $cache->clear();

        $output->writeln(sprintf('Cache has been purged in "app/cache/%s" ', basename($cache->getCacheDir())));
    }
}
