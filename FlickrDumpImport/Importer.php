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
    
    private function findFile($id) {
        if ($folders = scandir($this->getWorkingDirectory())) {
            foreach ($folders as $file) {
                if ($file != '.' && $file != '..') {
                    if (strpos($file, $id)) {
                        return $file;
                    }
                }
            }
        }
        
        return false;
    }
    
    private function importMedia() {
        
        // For each photo json
        if ($folders = scandir($this->getWorkingDirectory())) {
            foreach ($folders as $file) {
                if ($file != '.' && $file != '..') {
         
                    // Find file, and import
                    if (preg_match('/photo_([0-9]+)\.json/', $file)) {
                    
                        $json = json_decode(file_get_contents($file), true);
                        
                        \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Importing photo %d from %s...', [$json['id'], $file]));
        
                        // Find file
                        if ($file = $this->findFile($json['id'])) {
            
                            $ext = pathinfo($file, PATHINFO_EXTENSION);
                            
                            switch ($ext) {
                            
                                case 'gif': if (!$mime) $mime = 'image/gif';
                                case 'png': if (!$mime) $mime = 'image/png';
                                case 'jpeg':    
                                case 'jpg' : 
                                    if (!$mime) $mime = 'image/jpeg';
                                    
                                    \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Importing %s as a photo', [$file]));
        
				    $photo_obj = \IdnoPlugins\Photo\Photo::getOneFromAll(array('flickr_id' => $photo['id']));
				    if (!$photo_obj) {
					
					$photo_obj = new \IdnoPlugins\Photo\Photo();
					$photo_obj->flickr_id = $photo['id'];
					
					$_FILES = [
					    'photo' => [
						'tmp_name' => $file,
						'name' => basename($json['original']),
						'type' => $mime,
					    ]
					];
				    } else {
					
					\Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Photo has already been imported, so amending values'));
				    }

                                    break;
                                
                                case 'mp4' :
                                    $mime = 'video/mp4';
                                    \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Importing %s as a video', [$file]));
				    
				    $photo_obj = \IdnoPlugins\Media\Media::getOneFromAll(array('flickr_id' => $photo['id']));
				    if (!$photo_obj) {
					$photo_obj = new \IdnoPlugins\Media\Media();
					$photo_obj->flickr_id = $photo['id'];
					
					$_FILES = [
					    'photo' => [
						'tmp_name' => $file,
						'name' => basename($json['original']),
						'type' => $mime,
					    ]
					];
				    } else {
					\Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Video has already been imported, so amending values'));
				    }
				    
                                    break;
                                
                                default:
                                    throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('Extension %s is not supported for %s', [$ext, $file]));
                            }
                            
			    
			    $tags = [];
			    foreach ($json['tags'] as $tag) {
				$tags[] = '#' . trim(str_replace(' ', '', $tag['tag']) , '"#\'');
			    }

			    \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('\tSetting title as %s', [$json['name']]));
			    \Idno\Core\Idno::site()->currentPage()->setInput('title', $json['name']);

			    \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('\tSetting body as %s', [empty($tags) ? $json['description'] : $json['description'] . "\n\n" . implode(' ', $tags)]));
			    \Idno\Core\Idno::site()->currentPage()->setInput('body', empty($tags) ? $json['description'] : $json['description'] . "\n\n" . implode(' ', $tags));

			    \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('\tSetting created time to %s', [$json['date_taken']]));
			    \Idno\Core\Idno::site()->currentPage()->setInput('created', strtotime($json['date_taken']));
			    
			    if (!empty($json['geo'])) {
				\Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('\tSetting lat/long as %s/%s', [$json['geo']['latitude'], $json['geo']['longitude']]));
				$photo_obj->lat = $json['geo']['latitude'];
				$photo_obj->ong = $json['geo']['longitude'];
			    }

			    $photo_obj->flickr_page = $json['photopage'];
			    \Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('\tPhoto page saved as %s', [$json['photopage']]));

			    if ($photo_obj->saveDataFromInput())
				\Idno\Core\Idno::site()->logging()->info(\Idno\Core\Idno::site()->language()->_('Photo saved as %s', [$photo_obj->getUrl()]));

			    $photo_obj = null;
                            
                        } else {
                            throw new \RuntimeException(\Idno\Core\Idno::site()->language()->_('Could not find a file associated with %s', [$json['id']]));
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
