<?php

use Symfony\Component\Yaml\Yaml;

class PicoPageViews extends AbstractPicoPlugin
{
    const API_VERSION = 3;
    protected $enabled = true;
    protected $dependsOn = array();
    protected $md = './content/_stats.md'; // stats here
    private $statsPageMeta = array();
    
    public function onSinglePageLoading($id, &$skipPage)
    {
        if ($id === '_stats') { $skipFile = true; }
    }
    
    public function onCurrentPageDiscovered(
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
        $currentPage = $this->getPico()->getCurrentPage();
        if ($currentPage !== null) {
			
			// Open the file as read/write without erasing its contents (creating it if it doesn't exist)
            $file = fopen($this->md, 'c+');

			// Get an exclusive lock on the file; a shared lock for reading isn't appropriate, since another process could then read it before this one has updated it
            if (flock($file, LOCK_EX)) {
				
                $currentPageId = $this->getPico()->getCurrentPage()['id'];
				
				$this->loadStats($file);
                $this->statsPageMeta['stats'][$currentPageId]++;
                $this->saveStats($file);
				
				// Release the file lock
                flock($file, LOCK_UN);
				
            }
			
            fclose($file);

        }
    }

    private function loadStats($file)
    {
        $currentPageId = $this->pico->getCurrentPage()['id'];
		
        // Read the full contents of the stats file (but only if it has any contents)
		$fileLength = filesize($this->md);
        $frontMatter = $fileLength > 0 ? fread($file, $fileLength) : '';
		
		// Turn the contents into an array of data
        $frontMatterArray = $this->getPico()->parseFileMeta($frontMatter, []);        
        $this->statsPageMeta = $frontMatterArray;
        $this->statsPageMeta['stats'] = $this->statsPageMeta['stats'] ?? [];
		
		// If the current page isn't already in this array, then put it in
        if (!array_key_exists($currentPageId, $this->statsPageMeta['stats'])) {
            $this->statsPageMeta['stats'][$currentPageId] = 0;
        }                
    }
    
    private function saveStats($file)
    {
        // Convert our array of stats into a string of YAML
        $frontMatterArray = array('stats' => $this->statsPageMeta['stats']);
        $yaml = "---\n" . Yaml::dump($frontMatterArray) . "---\n";
		
		// Overwrite the stats file with our new data
		ftruncate ($file, 0);	// Empty the file
		rewind($file);			// Return to position 0
        fwrite($file, $yaml);
    } 
}

?>
