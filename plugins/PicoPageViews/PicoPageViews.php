<?php

use Symfony\Component\Yaml\Yaml;

class PicoPageViews extends AbstractPicoPlugin
{
    const API_VERSION = 3;
    protected $enabled = true;
    protected $dependsOn = array();
    protected $statsDir = './content/stats';	// Root of stats files
    private $statsPageMeta = array();
    
/*    public function onSinglePageLoading($id, &$skipPage)
    {
        if (strpos($id, 'stats/') === 0) { $skipPage = true; }
    }
*/

    /**
     * Triggered after Pico has read its configuration
     *
     * @see Pico::getConfig()
     * @see Pico::getBaseUrl()
     * @see Pico::getBaseThemeUrl()
     * @see Pico::isUrlRewritingEnabled()
     *
     * @param array &$config array of config variables
     *
     * @return void
     */
    public function onConfigLoaded(array &$config)
    {
        $this->template = null;
        if (isset($config['PicoPageViews']['template']))
            $this->template = $config['PicoPageViews']['template'];
		if (isset($config['PicoPageViews']['make_hidden']))
            $this->make_hidden = $config['PicoPageViews']['make_hidden'];
		if (isset($config['PicoPageViews']['ignore_addresses']))
            $this->ignore_list = $config['PicoPageViews']['ignore_addresses'];
    }

	
    public function onCurrentPageDiscovered(
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
		// Don't continue if the user is visiting from an IP address that we're ignoring
		if ($this->valid_address()) {
			
			date_default_timezone_set('America/Toronto');
			$this->currentDate = date('Y-m-d');
			$this->currentTime = date('H:i');
			$this->md = $this->statsDir . '/stats' . $this->currentDate . '.md';
			
			$currentPage = $this->getPico()->getCurrentPage();
			if ($currentPage !== null) {
				$currentPageId = $currentPage['id'];

				if ($currentPageId !== null && strpos($currentPageId, 'stats') !== 0) {		// Don't count stats pages themselves
					
					// Open the stats file as read/write without erasing its contents (creating it if it doesn't exist)
					$file = fopen($this->md, 'c+');

					// Get an exclusive lock on the file; a shared lock for reading isn't appropriate, since another process could then read it before this one has updated it
					if (flock($file, LOCK_EX)) {
						$this->loadStats($file);
						$this->statsPageMeta['stats'][$currentPageId]++;
						$this->saveStats($file);
						
						// Release the file lock
						flock($file, LOCK_UN);
					}
					
					fclose($file);

				}
			}
		
		}
    }
	
	
	private function valid_address()
	{
		if (
			array_key_exists('REMOTE_ADDR',$_SERVER)		// This global should always be set, but let's not take chances
			&& !empty($_SERVER['REMOTE_ADDR'])				// There should always be a value in there, but again, let's be safe
			&& isset($this->ignore_list) 					// Does an ignore list exist in the site config
			&& is_array($this->ignore_list)					// Is it an array of values as expected
		) {
			// If the user's address is in the ignore list, we state that it should not be counted
			if (in_array($_SERVER['REMOTE_ADDR'], $this->ignore_list))
				return false;
		}
		
		// In all other cases, count the visit from this address
		return true;
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
		
		// Update the datetime in the file
		$this->statsPageMeta['date'] = $this->currentDate . ' ' . $this->currentTime;
    }
    
    private function saveStats($file)
    {
        // Convert our array of stats into a string of YAML
        $frontMatterArray['stats'] = $this->statsPageMeta['stats'];
		$frontMatterArray['date'] = $this->statsPageMeta['date'];
		if(isset($this->template)) $frontMatterArray['template'] = $this->template;			// Update the template name, if it was specified in config.yml
		if(isset($this->make_hidden)) $frontMatterArray['hidden'] = $this->make_hidden;		// Update the "hidden" value, if it was specified in config.yml

        $yaml = "---\n" . Yaml::dump($frontMatterArray) . "---\n";
		
		// Overwrite the stats file with our new data
		ftruncate ($file, 0);	// Empty the file
		rewind($file);			// Return to position 0
        fwrite($file, $yaml);
    } 
}

?>
