<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ConsolePlugins\FlickrDumpImport;

/**
 * Importer class
 *
 * @author marcus
 */
class Importer {
    
    private $directory;
    private $user;
    
    public function __construct($user, $directory) {
        $this->user = $user;
        $this->directory = $directory;
        
        if (!($this->user instanceof \Idno\Entities\User)) 
            throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('The passed user is not a valid user.'));
        
        if (!is_dir($directory) || !is_readable($directory)) 
            throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('Directory %s is not a valid or readable directory', [$directory]));
    }
    
    private function checkEnvironment() {
        
        if (!\Idno\Core\Idno::site()->plugins()->get('Photo'))
            throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('FlickrDumpImport requries the Photo plugin, this does not appear to be installed or activated.'));
     
        if (!\Idno\Core\Idno::site()->plugins()->get('Media'))
            throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('FlickrDumpImport requries the Media plugin, this does not appear to be installed or activated.'));
        
        if (!\Idno\Core\Idno::site()->plugins()->get('VideoTranscode'))
            throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('FlickrDumpImport requries the VideoTranscode plugin, this does not appear to be installed or activated.'));
        
    }
    
    private function getWorkingDirectory() {
        return rtrim(\Idno\Core\Idno::site()->config()->getUploadPath(), '/\\') . DIRECTORY_SEPARATOR . 'FlickrDumpImport_' . md5($user->getUUID()) . DIRECTORY_SEPARATOR;
    }
    
    private function decompressDump() {
        
        if ($folders = scandir($this->directory)) {
            foreach ($folders as $file) {
                if ($file != '.' && $file != '..') {
                    
                    // Find a zip
                    if (strpos($file, '.zip')) {
                        
                        \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Decompressing %s ... ', [$file]));
                        
                        $zip = new \ZipArchive();
                        if ($zip->open($file) === true) {
                            $zip->extractTo($this->getWorkingDirectory());
                            $zip->close();
                        } else {
                            throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('There was a problem decompressing %s', [$file]));
                        }
                        
                    }
                    
                }
            }
        }        
    }

    public function doImport() {
        \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Starting import for %s on %s', [$user->getName(), date('r')]));
        
        // Check environment
        $this->checkEnvironment();
        
        // Make working directory
        mkdir($this->getWorkingDirectory(), 0777, true);
        
        try {
            
            // Decompressing file
            $this->decompressDump();
            
            // Log the user in
            \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Logging %s on', [$user->getName()]));        
            \Idno\Core\Idno::site()->session()->logUserOn($this->user);
            
            
            
            
            
        } catch (\Exception $ex) {
            \Idno\Core\Idno::site()->logging()->error($ex->getMessage());
        }
        
        
    }
}
