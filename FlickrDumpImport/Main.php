<?php

namespace ConsolePlugins\FlickrDumpImport {
    
    class Main extends \Idno\Common\ConsolePlugin {
        
        
        public function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
            
            $directory = $input->getArgument('directory');
            $username = $input->getArgument('username');
            $user = \Idno\Entities\User::getByHandle($username);
            if (!$user)
                throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('Could not find user %s', [$username]));
            
            $importer = new Importer($user, $directory);
	    
	    $importer->doImport();
        }

        public function getCommand() {
            return 'import-flickr';
        }

        public function getDescription() {
            return \Idno\Core\Idno::site()->language()->_('Import flickr photos and videos from a Flickr export archive.');
        }

        public function getParameters() {
            return [
                new \Symfony\Component\Console\Input\InputArgument('username', \Symfony\Component\Console\Input\InputArgument::REQUIRED, \Idno\Core\Idno::site()->language()->_('The username to import flickr dump as.')),
                new \Symfony\Component\Console\Input\InputArgument('directory', \Symfony\Component\Console\Input\InputArgument::REQUIRED, \Idno\Core\Idno::site()->language()->_('Directory to import files from.')),
            ];
        }
        
        function registerTranslations()
        {

            \Idno\Core\Idno::site()->language()->register(
                new \Idno\Core\GetTextTranslation(
                    'flickrdumpimport', dirname(__FILE__) . '/languages/'
                )
            );
        }

    }
}