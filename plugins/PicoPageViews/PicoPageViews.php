<?php

use Symfony\Component\Yaml\Yaml;

class PicoPageViews extends AbstractPicoPlugin
{
    const API_VERSION = 3;
	
    private $statsDir = 'stats';		// Default location to store stats md files (within Pico's /contents directory - so it's also a Pico page ID)
	private $template = null;			// By default, don't specify a template name in stats md files
	private $make_hidden = false;		// By default, don't add "hidden: true" to YAML frontmatter
	private $ignore_list = [];
	
    private $statsPageMeta = array();	// Data from the most recently loaded stats page
    private $allStatsPages;
    
	
    /***********************
     * Pico Event Handlers *
     ***********************/
	
/*    public function onSinglePageLoading($id, &$skipPage)
    {
        if (strpos($id, 'stats/') === 0) { $skipPage = true; }
    }
*/

    /**
     * After Pico has read its configuration, we capture config values specific to this plugin
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

	
	/**
     * Once the current page is known, we will capture information from the visitor, and add it to existing stored statistics
     *
     * @param array|null &$currentPage  data of the page being served
     * @param array|null &$previousPage data of the previous page
     * @param array|null &$nextPage     data of the next page
     *
     * @return void
     */
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
	
	
	/**
     * Once all pages are loaded, we will grab the ones containing our statistics
     *
     * @param array[] &$pages sorted list of all known pages
     */
    public function onPagesLoaded(array &$pages)
    {
        //$this->allStatsPages = $pages[$this->statsDir];
    }
	
	
    /*********************
     * Private Functions *
     *********************/

	/**
     * Check whether the current visitor's IP address is not in the ignore list
     *
     * @return boolean whether this visitor should be counted
     */
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
	
	/**
     * Create an array of data ($this->statsPageMeta) derived from the YAML frontmatter stored in a given file
     *
     * @param filehandle $file  an open handle to a markdown file
     *
     * @return void
     */
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
	

	/**
     * Write an array of data into YAML frontmatter to a given file (deleting all existing contents)
     *
     * @param filehandle $file  a handle to a markdown file opened for writing
     *
     * @return void
     */
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
		//error_log('Referer: "' . $referer . '"');
		
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


    /********************
     * Public Functions *
     ********************/

	/**
     * Return the raw number of page hits for the specified year, month, or date.
	 * If only the year is provided, it will return all hits in that entire year.
	 * If year and month are provided (but no date), it will return all hits in that entire month.
	 * If a larger unit is omitted but then a smaller unit is provided, assume the larger unit is the current year/month.
	 * If all parameters are omitted, the current date is used.
	 * If an invalid date is provided, a null value will be returned.  Such cases include:
	 * - a day is specified that does not exist in the specified month (29, 30, 31)
	 * - a month or day is specified outside the valid range
	 * - a year/month/day is specified for which no statistics files exist
     *
     * @param int|null $year	desired year (4 digits)
     * @param int|null $month	desired month (from 1 to 12)
     * @param int|null $day		desired day (from 1 to 31)
     *
     * @return int|null total page hits
     */
	public function getHits (
		$year = null,
		$month = null,
		$day = null
	) {
		$total_hits = 0;
		
		// If a day has been provided (or nothing has been provided at all):
		if ( !is_null($day) || (is_null($year) && is_null($month) && is_null($day)) ) {
			$day = $day ?? date('d');
			$month = $month ?? date('m');
			$year = $year ?? date('Y');
			
			// Check the day is valid for the given month
			if ($day < 1 || $day > date('t', date_create_from_format('m', $month))) {
				return null;
			}
			
			// Check the month is valid
			if ($month < 1 || $month > 12) {
				return null;
			}
			$month = date('m', date_create_from_format('m', $month));	// Ensure the month has 2 digits, zero-padded



			// Try to find that date in the stats pages discovered by Pico



			// See if a file exists for this date
			$filename = 'stats' . $year . '-' . $month . '-' . $day . '.md';
			if (!file_exists($filename)) {
				return null;
			}
			
			// Open the filestream, and get a shared lock on the file for reading (which will block attempts to write, but be blocked indefinitely if it's already locked for writing)
			$file = fopen($this->statsRoot . '/' . $filename, 'r');
			if (flock($file, LOCK_SH)) {
				$this->loadStats($file);
				flock($file, LOCK_UN);		// Release the file lock
			}
			fclose($file);
			
			

		}
	}
}

?>
