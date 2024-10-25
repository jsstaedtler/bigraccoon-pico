<?php

use Symfony\Component\Yaml\Yaml;

class PicoPageViews extends AbstractPicoPlugin
{
    const API_VERSION = 3;
    protected $dependsOn = array();
	
    private $statsDir = 'stats';			// Default location to store stats md files (within Pico's /contents directory)
	private $template = null;				// By default, don't specify a template name in stats md files
	private $make_hidden = false;			// By default, don't add "hidden: true" to YAML frontmatter
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
		// Define the root storage location with the default value, which can be overridden by a custom value in config.yml
		$this->statsRoot = './content/' . $this->statsDir;
		
        if (isset($config['PicoPageViews']['content_folder'])) {
			// Ensure the directory name provided in the config file is a valid one (and not something that might blow up the filesystem)
			if (preg_match('#^[\w][\w\-. \/]*$#', $config['PicoPageViews']['content_folder'])) {
				$this->statsRoot = './content/' . $config['PicoPageViews']['content_folder'];
			} else {
				error_log('PicoPageViews: Invalid value given for config option "content_folder".  Using default value instead: ' . $this->statsDir);
			}
		}

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
		if ($this->isValidAddress()) {
			
			date_default_timezone_set('America/Toronto');
			$this->currentDate = date('Y-m-d');
			$this->currentTime = date('H:i');
			$this->md = $this->statsRoot . '/stats' . $this->currentDate . '.md';
			
			$currentPage = $this->getPico()->getCurrentPage();
			if ($currentPage !== null) {
				$currentPageId = $currentPage['id'];

				// Don't count visits to stats pages themselves (which will have a page ID beginning with the stats directory)
				if ($currentPageId !== null && strpos($currentPageId, $this->statsDir) !== 0) {
					
					// Open the stats file as read/write without erasing its contents (creating it if it doesn't exist)
					$file = fopen($this->md, 'c+');

					// Get an exclusive lock on the file; a shared lock for reading isn't appropriate, since another process could then read it before this one has updated it
					if (flock($file, LOCK_EX)) {
						$this->loadStats($file);
						$this->updateStats($file);
						
						// Release the file lock
						flock($file, LOCK_UN);
					}
					
					fclose($file);

				}
			}
		
		}
    }
	
	
	private function isValidAddress()
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
         // Read the full contents of the stats file (but only if it has any contents)
		$fileLength = filesize($this->md);
        $frontMatter = $fileLength > 0 ? fread($file, $fileLength) : '';
		
		// Turn the contents into an array of data
        $frontMatterArray = $this->getPico()->parseFileMeta($frontMatter, []);        
        $this->statsPageMeta = $frontMatterArray;
        $this->statsPageMeta['visitors'] = $this->statsPageMeta['visitors'] ?? [];
        $this->statsPageMeta['referers'] = $this->statsPageMeta['referers'] ?? [];
	}
	
	
	private function updateStats($file)
	{
		$currentPageId = $this->pico->getCurrentPage()['id'];

		// 'visitors' is an associative array with Pico page IDs for keys.  For each page visit, we collect the IP address & user agent.
		// Often enough, each unique visitor will have that consistent pair of values, and we only count each unique pair once per page.
		// (Note the possibility of a missing UA string, normally only seen with bots/crawlers)
		$visitor = [
			'ip' => $_SERVER['REMOTE_ADDR'],
			'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
		];
		
		// 'referers' similarly uses Pico page IDs for keys.  For each page visit, we check the referrer URL, and keep a count of each one.
		// This will help indicate whether any one particular referrer is driving lots of traffic compared to others.
		// On top of that, by summing the total for every referrer to a single Pico page, we will have the total hits for that page.
		// (Note that the referrer may not be present, for various reasons)
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		error_log('Referer: "' . $referer . '"');
		
		
		// If the current page ID isn't already in the visitors array, add it in:
        if ( !array_key_exists($currentPageId, $this->statsPageMeta['visitors']) ) {
            $this->statsPageMeta['visitors'][$currentPageId] = [];
        }
		
		// Similarly, if the current page ID isn't already in the referers array, add it in:
        if ( !array_key_exists($currentPageId, $this->statsPageMeta['referers']) ) {
            $this->statsPageMeta['referers'][$currentPageId] = [];
        }
		
		// If this visitor is not already present, add them
		if ( !in_array($visitor, $this->statsPageMeta['visitors'][$currentPageId]) ) {
            array_push($this->statsPageMeta['visitors'][$currentPageId], $visitor);
        }
		
		// Increment the count for this referer, initializing it first if not yet present
		if ( !array_key_exists($referer, $this->statsPageMeta['referers'][$currentPageId]) ) {
            $this->statsPageMeta['referers'][$currentPageId][$referer] = 0;
        }
		$this->statsPageMeta['referers'][$currentPageId][$referer]++;
		
		
		// Update the datetime in the file
		$this->statsPageMeta['date'] = $this->currentDate . ' ' . $this->currentTime;

		// Convert our array of stats into a string of YAML
		$frontMatterArray['visitors'] = $this->statsPageMeta['visitors'];
		$frontMatterArray['referers'] = $this->statsPageMeta['referers'];
		
		$frontMatterArray['date'] = $this->currentDate . ' ' . $this->currentTime;		// Include the current datetime
		if(isset($this->template)) $frontMatterArray['template'] = $this->template;		// Provide the template name, which can be customized in config.yml
		if(isset($this->make_hidden)) $frontMatterArray['hidden'] = $this->make_hidden;	// Provide the "hidden" status, which can be customized in config.yml

		$yaml = "---\n" . Yaml::dump($frontMatterArray, 3) . "---\n";	// '3' means the first 3 levels are expanded YAML, more than that get condensed
		
		// Overwrite the stats file with our new data
		ftruncate($file, 0);	// Empty the file
		rewind($file);			// Return to position 0
		fwrite($file, $yaml);
			
	}
	
}

?>
