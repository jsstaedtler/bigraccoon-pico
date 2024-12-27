<?php
/**
 * This file is for use with the Pico web CMS. Pico can be found at:
 *
 * <https://github.com/picocms/Pico>
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

/**
 * ReaderReactions - a Pico plugin that provides readers with a quick and simple way to react to pages on the site.
 *
 * This could simply be in the form of a "like" button, represented by a "heart" icon.  When a reader clicks it, it can change to indicate it was clicked, and the number of "likes" will increase by 1.  That number can optionally be displayed beside the button, or kept hidden for only the site owner to see.  This button can be placed on every page, with each page having its own cumulative "likes" total.
 *
 * This plugin can also support any arbitrary number of reaction types, each maintaining its own total per page.  Some possible examples are:
 * - Thumbs up, thumbs down
 * - Smiley face, neutral face, sad face
 * - 5 stars (with values of 1, 2, 3, 4, and 5)
 * - 10 stars (with values of 1 to 10)
 * - A heart, a laughing face, a thinking face, a shocked face, a thumbs down
 *
 * In many cases, the reader may select only one out of the available options (like "radio button" behaviour), but you can configure the plugin to permit multiple selections (like "checkbox" behaviour).
 *
 * It is up to the site owner to decide what each reaction type should mean, and what icons, images, or buttons should be displayed.  This plugin will provide Pico variables to be used in a Twig theme file, where image files or regular text (even emoji) can be employed as desired.  The count totals can also be used in different ways, either displayed as-is, or perhaps combined into an average (eg. for "star" ratings).
 *
 * This plugin provides no foolproof way to prevent any single reader from submitting more than one reaction per page; eg. a reader could technically "like" a page more than a dozen times, impossible to distinguish from 12 readers each liking it once.  You can enable the use of an identifying cookie, and this plugin will record all reactions from that ID to ensure they only make one per page.  If you choose not to enable the cookie, or a reader's browser rejects the cookie, this plugin will track them by IP address.  Since there will always be the possibility of abuse, it is not recommended to rely on this plugin for important statistical measurements or for any serious voting process.  It is primarily to allow and encourage readers to give quick feedback.
 *
 * Possible future features:
 *  * Integrate with PicoAuth so only logged-in readers can react (for more reliable statistics)
 *  * "Up" and "down" buttons to raise or lower a shared count
 *  * Allow counts to be displayed as a percentage of total reactions
 *  * Selecting from a scale, so all icons up to the one selected appear selected as well (eg. 5-star ratings)
 *  * Use page metadata to override the site-wide config with custom per-page settings
 *
 * @author  J.S. Staedtler
 * @link    https://bigraccoon.ca
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 */

use Symfony\Component\Yaml\Yaml;

class ReaderReactions extends AbstractPicoPlugin
{
    /**
     * API version used by this plugin
     *
     * @var int
     */
    const API_VERSION = 3;

    /**
     * Name of the directory (under "content") where data will be stored.  This may change when Pico config is loaded.  Default: 'reactions'
     *
     * @var string
     */
	protected $contentDir = 'reactions';
	
   /**
     * Name of the directory (under the Pico site root) where image files will be available.  This may change when Pico config is loaded.  Default: 'assets'
	 * In this location, there must be a pair of image files (sharing the same file extension) for every reaction type that has been defined.  One will have the name of the reaction type, and the other will have the same name with a suffix of "-selected".  For example:
	 * - thumbsup.png
	 * - thumbsup-selected.png
	 * - thumbsdown.png
	 * - thumbsdown-selected.png
     *
     * @var string
     */
	protected $imageDir = 'assets';
	
   /**
     * File extension of the image files.  Default: 'png'
     *
     * @var string
     */
	protected $imageExt = 'png';
	
    /**
     * A list of what reaction options should be available to readers on every page of the site.
	 * These will be presented as icons or buttons, optionally with the total count displayed alongside.
	 * Should be an array, with elements consisting of associative arrays of the form: ['name' => 'a_descriptive_string', 'display_count' => true|false]
	 * If a single string is provided, it will be turned into an array of a single element with that string as the 'name', and 'display_count' => true
	 * The 'name' serves as an ID for a unique reaction type, as well as a description of the reaction (it is used as the "alt" string).  Since it is the basis for image filenames, the 'name' should typically have no spaces, and it can be considered case-sensitive.
     *
     * @var string|array
     */
	protected $reactTypes = [
		['name' => 'ThumbsUp', 'display_count' => true],
		['name' => 'ThumbsDown', 'display_count' => false],
	];

    /**
     * Whether the reader may select more than one option on any single page
     *
     * @var bool
     */
	protected $multiSelect = false;
	
    /**
     * This is the "anchor", or element ID, that the page will jump to when the user clicks a reaction button.  Since the page must reload
	 * to submit the reaction, the anchor should be at or slightly above the reactions form (so that the user can immediately see the effect
	 * of their selection).  The <form> element itself has id="reactions", so that is the default value.
     *
     * @var string
     */
	protected $anchorID = 'reactions';

	/**
     * Whether to use an identifying cookie to track reactions by a single reader
	 *
	 * When the reader first submits a reaction, a unique GUID will be generated, and stored in a cookie in the reader's browser.  That ID will be stored along with its reaction for each page, so that they persist across visits and page reloads.  This provides a consistent experience to the reader, and prevents readers from easily repeating or spamming reactions.
	 * You may need to disclose the use of this tracking cookie, and provide an obvious opt-out mechanism -- although that functionality is not built-in to this plugin.  The cookie will be HTTPS-only and samesite-only.
	 * If this is set to false (or if the cookie can not be set in the reader's browser), the reader's reaction will be recorded as "anonymous".  If they reload the page, they will be able to react anew.
	 * Note that a reader can delete the cookie from their browser, so that a new one with a new ID will be generated, permitting them to react to pages more than once.  This feature can not prevent intentional abuse from a bad actor.
     *
     * @var bool
     */
	protected $useCookie = true;
	
	const FORM_NAME = 'reader_reactions';			// A unique value to distinguish our POST submissions from any others
	const COOKIE_NAME = 'PicoReaderReactions';		// If you change this, all previously issued cookies will become unreadable.
	protected $newCookieCreated = false;
	protected $newCookieFailed = false;
	protected $jsNeeded = false;					// Determines whether this plugin needs to insert some JavaScript in the rendered page
	protected $siteUrl;
	protected $rootDir;
	protected $currentPageID;
	protected $currentPageUrl;
	protected $currentUserID = '';
	protected $currentReactions;
	protected $currentReactionCounts;


    /***********************
     * Pico Event Handlers *
     ***********************/
	
    /**
     * Get any custom configuration belonging to our plugin
     *
     * @param array &$config array of config variables
     */
    public function onConfigLoaded(array &$config)
    {
		$this->siteUrl = $config['base_url'];
		
		// content_directory
        if (isset($config['ReaderReactions']['content_directory'])) {
			// Ensure the directory name provided in the config file is a valid one (and not something that might blow up the filesystem when created)
			if (preg_match('#^[\w][\w\-. \/]*$#', $config['ReaderReactions']['content_directory'])) {
				$this->contentDir = $config['ReaderReactions']['content_directory'];
			} else {
				throw new UnexpectedValueException(
					'ReaderReactions plugin: Invalid value given for config parameter "content_directory".  Using default value instead: ' . $this->contentDir
				);
			}
		}
        $this->rootDir = './content/' . $this->contentDir;
		
		// image_directory
        if (isset($config['ReaderReactions']['image_directory'])) {
			$this->imageDir = $config['ReaderReactions']['image_directory'];
		}

		// image_extension
        if (isset($config['ReaderReactions']['image_extension'])) {
			$this->imageExt = $config['ReaderReactions']['image_extension'];
		}
		
		// react_types
		if (isset($config['ReaderReactions']['react_types'])) {
			
			// See if user provided a simple string
            if (is_string($config['ReaderReactions']['react_types'])) {
				
				$this->reactTypes = [
					['name' => $config['ReaderReactions']['react_types'], 'display_count' => true],
				];
				
			} else if (is_array($config['ReaderReactions']['react_types'])) {
				
				// Although this is an array, there's no simple way to test whether it's formatted correctly, so there will be runtime checking later
				$this->reactTypes = $config['ReaderReactions']['react_types'];
				
			} else {
				throw new UnexpectedValueException(
					'ReaderReactions plugin: Value of config parameter \'react_types\' is not of type array or string'
				);
			}
		}
		
		// multi_select
		if (isset($config['ReaderReactions']['multi_select'])) {
			if (is_bool($config['ReaderReactions']['multi_select'])) {
				$this->multiSelect = $config['ReaderReactions']['multi_select'];
			} else {
				throw new UnexpectedValueException(
					'ReaderReactions plugin: Invalid value given for config parameter "multi_select"; must be boolean.  Using default value instead: ' . $this->multiSelect
				);
			}
		}
		
		// anchor_id
        if (isset($config['ReaderReactions']['anchor_id'])) {
			$this->anchorID = $config['ReaderReactions']['anchor_id'];
		}

		// use_cookie
		if (isset($config['ReaderReactions']['use_cookie'])) {
			if (is_bool($config['ReaderReactions']['use_cookie'])) {
				$this->useCookie = $config['ReaderReactions']['use_cookie'];
			} else {
				throw new UnexpectedValueException(
					'ReaderReactions plugin: Invalid value given for config parameter "use_cookie"; must be boolean.  Using default value instead: ' . $this->useCookie
				);
			}
		}
		
    }
	
	
	/**
     * 
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
		if ($currentPage !== null) {
			$this->currentPageID = $currentPage['id'];
			$this->currentPageUrl = $currentPage['url'];
			
			// Load all existing reactions already saved for this page
			$this->loadReactions($this->currentPageID);
			
			// Determine the ID for this user based on whether we can tell they've visited before
			$this->currentUserID = $this->getUserID();
			
			
			// Check if the page is responding to a POST request from a ReaderReactions button
			if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_name']) && $_POST['form_name'] == self::FORM_NAME) {
				
				if (isset($_POST['i'])) {
					// The previous run had set a new cookie, and we need to validate that it was accepted.  It has submitted the value ("ID") of the new cookie in the 'i' field.  If that cookie was created successfully, then $this->getUserID() would have read it and saved it to $this->currentUserID.  So by comparing the 'i' field to $this->currentUserID, we know if creation succeeded.
					
					if ($_POST['i'] != $this->currentUserID) {
						// Creation failed, so this user may be refusing cookies.  Instead we will track them via IP address.
						$this->currentUserID = $_SERVER['REMOTE_ADDR'];
						$this->$newCookieFailed = true;
					}
				}
				
				if (isset($_POST['name'])) {
					// Start a new array to store the info to be returned
					$newCount = [];
					
					// Check if this user has already made a selection in the past
					$existingSelection = $this->userAlreadySelected($this->currentUserID);
					
					// If this reaction had already been selected by the user, clicking it again will undo that selection
					if ($_POST['name'] == $existingSelection) {
						$newCount[] = ['name' => $_POST['name'], 'count' => $this->recordReaction($_POST['name'], -1)];
					} else {
						// If multiselect is disabled, and the user selected a *different* reaction type in the past, undo it
						if (!$this->multiSelect and $existingSelection) {
							$newCount[] = ['name' => $existingSelection, 'count' => $this->recordReaction($existingSelection, -1)];
						}
						
						// Finally, record their new selection
						$newCount[] = ['name' => $_POST['name'], 'count' => $this->recordReaction($_POST['name'], 1)];
					}
					
					// Now return the new total for this reaction, so the originating JavaScript can update the original page
					echo json_encode($newCount);
				} else {
					// If the name field is missing, we can't do anything
					http_response_code(400);
				}
				
				// Since the page was loaded by a POST submission, we should not output any other page data
				exit();
			}
			
		}
	}
	

    /**
     * Provide custom variables for Twig templates
     *
     * @param string &$templateName  file name of the template
     * @param array  &$twigVariables template variables
     */
    public function onPageRendering(&$templateName, array &$twigVariables)
    {
		if ($this->currentPageID) {
			// Provide the up-to-date reaction types and counts as a Twig variable
			$twigVariables['reaction_counts'] = $this->currentReactionCounts;
			
			// This variable contains raw HTML for a form that includes all available reaction types
			$twigVariables['reaction_form'] = $this->buildHTMLForm();
		}
	}
	
	
	/**
     * After Pico has fully rendered the HTML page, we can add additional content
     *
     * @param string &$output contents which will be sent to the user
     */
    public function onPageRendered(&$output)
    {
		// Some JavaScript must be inserted into this page for reaction buttons to work, but we don't want to tack entire scripts onto every single page of the site if they don't need it.  We only do so if the template has used any of the provided Twig variables or functions.
		
		// We will insert the scripting immediately before the end of the HTML body element, since it must execute only after the proper HTML elements have been added.

		if ($this->jsNeeded) {
			$output = str_ireplace('</body>', $this->buildJavaScript() . '</body>', $output);
		}
    }
	

    /**
     * Register custom Twig functions and filters
     *
     * @param Twig_Environment &$twig Twig instance
     */
    public function onTwigRegistered(Twig_Environment &$twig)
    {
		$twig->addFunction(
			new Twig_Function('reaction_is_selected', [$this, 'isReactionSelected'])
		);
    }
	
	
	
	/*********************
     * Private Functions *
     *********************/

	private function buildHTMLForm()
	{
		// This will produce the base HTML elements, so that the static reaction images and current totals will appear on the page.
		// Interaction with the images relies on JavaScript, and some JS scripting will be appended afterward.  Even if the user has JS
		// disabled, the current reaction totals will still be displayed.
		
		$form = <<<END
			<ul id="reader-reactions" class="reader-reactions">
				
			END;
			
		foreach ($this->reactTypes as $type) {
			$filename = $this->userAlreadySelected($this->currentUserID, $type['name']) ? $type['name'] . '-selected' : $type['name'];
			$classes = $this->userAlreadySelected($this->currentUserID, $type['name']) ? 'reaction-button selected' : 'reaction-button';

			$form .= <<<END
					<li>
						<img src="/{$this->imageDir}/{$filename}.svg" id="button-{$type['name']}" class="{$classes}" alt="{$type['name']}">

			END;
			
			if ($type['display_count']) {
				$form .= <<<END
						<span id="count-{$type['name']}" class="reaction-count">{$this->currentReactionCounts[$type['name']]}</span>
						
			END;
			}
					
			$form .= <<<END
					</li>
						
			END;
		}
		
		$form .= <<<END
			</ul>
		END;
		
		$this->jsNeeded = true;

		return $form;
	}
	


	private function buildJavaScript()
	{
		$formName = self::FORM_NAME;
		
		// We need JavaScript to add a click event handler to each reaction image.  Clicking on an image will send a POST request to this very same web page (like an HTML form would be submitted), and this plugin will catch the submission to record the change (see onCurrentPageDiscovered())
		
		// This is the POST data to be sent when a reader clicks a reaction button.  It is a string of JavaScript to be interpreted on-click.
		$postString = "'form_name={$formName}&name=' + e.target.id.replace('button-','')";
		
		// If we have not established whether the browser permits cookies, send the current ID value in the "i" field
		if ($this->useCookie and ($this->newCookieCreated or $this->newCookieFailed)) {
			$postString .= " + '&i={$this->currentUserID}'";
		}
		
		
		$form = <<<END
			<script>
			// Produced by ReaderReactions plugin for Pico
			
			END;
			
		// Add event handlers for clicking on any of the reaction buttons
		foreach ($this->reactTypes as $type) {
			$form .= <<<END
					document.getElementById('button-{$type['name']}').addEventListener('click', toggleReaction);
					
			END;
		}
		
		$form .= <<<END
				function addClickToButtons() {
					for (const el of document.getElementById('reader-reactions').children) {
						el.firstElementChild.addEventListener('click', toggleReaction);
						el.firstElementChild.style.cursor = 'pointer';
						el.firstElementChild.style.opacity = '100%';
					}
				}
				
				function removeClickFromButtons() {
					for (const el of document.getElementById('reader-reactions').children) {
						el.firstElementChild.removeEventListener('click', toggleReaction);
						el.firstElementChild.style.cursor = 'initial';
						el.firstElementChild.style.opacity = '30%';
					}
				}
				
				function toggleSelection(el) {
					if (el.classList.contains('selected')) {
						el.src = el.src.replace('-selected','');
					} else {
						el.src = el.src.replace(/\\.[^\\.]*$/, "-selected$&");
					}
					el.classList.toggle('selected');
				}
				
				function toggleReaction(e) {
					// Disable every button until we get the result back
					removeClickFromButtons();
					
					// Toggle the button to its new state, so the user gets immediate feedback (which we will revert upon failure)
					toggleSelection(e.target);
					
					var req = new XMLHttpRequest();
					req.open('POST', '', true);		// (method, URL, asynchronous)
					req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					
					req.onreadystatechange = () => {
						if (req.readyState === XMLHttpRequest.DONE) {
							if (req.status === 200) {
								// Get the JSON data returned from the POST request, which tells us every reaction type that has changed
								result = JSON.parse(req.responseText);
								// Loop through the result, updating the number contained in each <span> element
								for (const type of result) {
									document.getElementById('count-' + type.name).textContent = type.count;
								}
							} else {
								// Something went wrong, so revert the change to the button state
								toggleSelection(e.target);
							}
							
							// Now that the request has been completed, make buttons clickable again
							addClickToButtons();
						}
					}
					
					req.send($postString);
				}
				
				addClickToButtons();
			</script>
			
			END;

		return $form;
	}


    /**
     * Get the user's ID string.  If it can be saved in a cookie in the reader's browser, that ID can persist across pageviews and over time.
	 * If the cookie can't be set, the ID will be based on the user's IP address.
     *
	 * @return string	The user's unique ID
     */
	private function getUserID()
	{
		if ($this->useCookie) {
			
			// If there already exists a cookie containing the user's ID, just collect its value
			if (isset($_COOKIE[self::COOKIE_NAME])) {
				return $_COOKIE[self::COOKIE_NAME];
				
			} else {
				// Check whether this user's IP address has made any selections before
				if ($this->userAlreadySelected($_SERVER['REMOTE_ADDR'])) {
					// We can assume they're refusing cookies, and we should keep going by their IP address
					$this->newCookieFailed = true;
					return $_SERVER['REMOTE_ADDR'];
				}
				
				// Otherwise, we will create a new ID to store in a cookie for this user
				$newID = bin2hex(random_bytes(16));
				
				/* Here is a conundrum: the cookie is delivered in the HTTP response header, followed by the page content.
				   Therefore, during the current iteration of this script, the new cookie does not yet exist in the user's browser.
				   After this script concludes, and the page content has been delivered to the user's browser, only then can the browser
				   store the cookie and include it in future request headers (so future iterations will be able to read it).
				   
				   So how can we determine whether this new cookie will actually be accepted and stored in the user's browser?
				   Because, if it is not accepted, then a new cookie with a new id will be blindly generated again on the next run, ad infinitum.
				   We would prefer to know the cookie can not be set, and instead use the user's IP address as an ID.
				   
				   To test the existence of the cookie, we will wait until the reader submits a reaction.  In that POST submission will be
				   the ID value we stored in the new cookie.  When that causes a new instance of this script to execute, it will test whether
				   that cookie exists.  If not, we will assume the cookie was refused, and we will use the reader's IP address instead.
				*/
				
				$cookieOptions = [
					'expires' => strtotime('+1 years'),
					'path' => '/',
					'secure' => true,
					'httponly' => true,
					'samesite' => 'Lax',	// Limited to this domain only, but it will be used no matter what site the user comes from
				];
				setcookie(self::COOKIE_NAME, $newID, $cookieOptions);
				$this->newCookieCreated = true;
				return $newID;
			}

		} else {
			// We are not using cookies, so use the client's IP address instead
			return $_SERVER['REMOTE_ADDR'];
		}
		
	}
	

    /**
     * Load existing reaction totals for a given page ID
     *
     * @param string	$pageID			Pico page ID of the current page
	 * 
	 * @return array	Keys are reaction types, values are the total count
     */
	private function loadReactions($pageID)
	{
		// Initialize the arrays for counting the existing totals (in case saved data is incomplete/missing)
		$counts = [];
		foreach ($this->reactTypes as $type) {
			$this->currentReactions[$type['name']] = [];
			$counts[$type['name']] = 0;
		}
		
		// Path of file where this page's reactions are stored
        $filepath = $this->rootDir . "/" . $pageID . '.md';
		if (file_exists($filepath)) {
		
			// Open the filestream, and get a shared lock on the file for reading (which will block attempts to write, but be blocked indefinitely if it's already locked for writing)
			$file = fopen($filepath, 'r');
			if ($file && flock($file, LOCK_SH)) {
				
				// Read the full contents of the file (but only if it has any contents)
				$fileLength = filesize($filepath);
				$frontMatter = $fileLength > 0 ? fread($file, $fileLength) : '';
				
				flock($file, LOCK_UN);		// Release the file lock

				
				// Turn the contents into an array of data
				$frontMatterArray = $this->getPico()->parseFileMeta($frontMatter, []);
				
				if (array_key_exists('reactions', $frontMatterArray)) {
					
					// Store the data that was present in the file
					$this->currentReactions = $frontMatterArray['reactions'];
					
					// For each reaction type, there is an array of reader IDs and their count totals.  Generally, each reader can contribute just 1 or 0 to the total for that type, but the "anonymous" total can have any value (in cases where we aren't keeping track of the reader).
					
					// We will iterate through the "canonical" list of types from the plugin config, in case not all of them are present in this file (eg. if the types had been changed at some point after counts had been saved)
					foreach ($this->reactTypes as $type) {
						$typeName = $type['name'];

						if (array_key_exists($typeName, $this->currentReactions)) {
							foreach ($this->currentReactions[$typeName] as $readerID => $total) {
								$counts[$typeName] += $total;
							}
						} else {
							// Put this missing reaction type into the data
							$this->currentReactions[$typeName] = [];
						}
					}
					
				}
			}
			
			fclose($file);
		}
		
		$this->currentReactionCounts = $counts;		
	}
		
		
    /**
     * Save a reaction made (or undone) by the reader
     *
	 * @param string	$reactionName	Name of the reaction being made/undone
	 * @param int		$increment		How much to add to the existing count (default: +1, but specify -1 to undo a reaction)
     */
	private function recordReaction($reactionName, $increment = 1)
	{
		// Path of file where this page's reactions are stored
        $filePath = $this->rootDir . "/" . $this->currentPageID . '.md';

		// Check if the .md file already exists
        if (!file_exists($filePath)) {
			// The file doesn't exist, and it will be created later, but the containing directory must exist beforehand
			$dir = pathinfo($filePath, PATHINFO_DIRNAME);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
        }
		
		if (!isset($this->currentReactions[$reactionName][$this->currentUserID])) {
			// If this user ID is not present in the current data, create it, but don't start at a negative value
			$this->currentReactions[$reactionName][$this->currentUserID] = ($increment > 0 ? $increment : 0);
			$this->currentReactionCounts[$reactionName] += ($increment > 0 ? $increment : 0);
		} else {
			$this->currentReactions[$reactionName][$this->currentUserID] += $increment;
			$this->currentReactionCounts[$reactionName] += $increment;
		}
		
		// Arrange YAML frontmatter based on our reactions data
		$newFrontMatter = ['reactions' => $this->currentReactions];
		$newFileContents = "---\n" . Yaml::dump($newFrontMatter, 5) . "---\n";	// '5' means the first 5 levels are expanded YAML, more than that get condensed
		
		// Open the reactions file as read/write (without erasing its contents)
		$file = fopen($filePath, 'c+');

		// Get an exclusive lock on the file
		if (flock($file, LOCK_EX)) {
			// Now we can erase the file and insert our new data
			ftruncate($file, 0);	// Empty the file
			rewind($file);			// Return to position 0
			fwrite($file, $newFileContents);

			// Release the file lock
			flock($file, LOCK_UN);
		}
		
		fclose($file);
		
		// Return the new count of the reaction type that was updated
		return $this->currentReactionCounts[$reactionName];
	}
	

    /**
	 * Checks whether a specific user has previously selected a specific reaction type.  Returns false if they have made none.
	 * Otherwise, returns the name of the selected type as a string.
     * By omitting the $specificType parameter, this checks if the user has already selected *any* reaction, and returns only the first one.
	 * If multiselect mode is enabled, and $specificType is null, treat the result as a boolean for whether the user has selected anything at all.
     *
	 * @param 	string|null		$specificType		Reaction type to specifically test for
	 *
     * @return	string|false	Name of the reaction type already selected, or false if none
     */
	private function userAlreadySelected($userID, $specificType = null)
	{
		if (($specificType === null) and (!$this->multiSelect)) {

			// Cycle through all available reaction types
			foreach ($this->reactTypes as $type) {
				if (array_key_exists($userID, $this->currentReactions[$type['name']])) {
					if ($this->currentReactions[$type['name']][$userID] > 0)
						return $type['name'];
				}
			}

		} else if ($specificType !== null) {
			
			// Just test the specified reaction type name
			if (array_key_exists($userID, $this->currentReactions[$specificType])) {
				if ($this->currentReactions[$specificType][$userID] > 0)
					return $specificType;
			}
			
		}
		
		return false;
	}
	
	
	
	/*********************
     * Public Functions  *
	 * to expose to Twig *
     *********************/

    /**
	 * Checks whether the current user has previously selected a specific reaction type.
     *
	 * @param 	string		$reactionName		Reaction type to test for
	 *
     * @return	bool		Whether the current user has already selected the given type
     */
	public function isReactionSelected($reactionName)
	{
		if ($this->userAlreadySelected($this->currentUserID, $reactionName)) {
			return true;
		} else {
			return false;
		}
	}
	
}
