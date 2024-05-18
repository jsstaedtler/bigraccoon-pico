<?php

/**
* Plugin implementing comments in Pico
* @author kroche
* @license http://opensource.org/licenses/MIT
*/

class PicoComments extends AbstractPicoPlugin
{
    protected $enabled = true;      // whether this plugin is enabled by default sitewide
    protected $content_path;		// content storage path
    protected $headers;             // current page headers ("meta", "frontmatter", NOT HTML headers)
    protected $id;                  // current page URL ("id")
    protected $num_comments = 0;    // number of comments on this page


    private function createComment($author, $content, $reply_guid) {
        $guid = bin2hex(random_bytes(16));      // create a GUID for this comment
        $date = time();                         // get the current time
        $ip = $_SERVER['REMOTE_ADDR'];          // get the ip address the comment is posted from
        
        // user input sanitization
        $author = strlen($author) != 0 ? filter_var($author, FILTER_SANITIZE_STRING) : null;
        $content = strlen($content) != 0 ? htmlspecialchars($content, ENT_QUOTES, ini_get("default_charset")) : null;

        // fail if author or content is empty, or if content is larger than the comment size limit
        if (!isset($content)) {
            return 'Invalid comment content';
        }
		
		// If author is empty, call them "Anonymous"
		if (!isset($author)) {
			$author = 'Anonymous';
		}
		
		// Fail if author length exceeds the limit
		if (strlen($author) > $this->getPluginConfig("name_size_limit")) {
			return 'Name exceeds character limit: ' . strlen($author) . ' > ' . $this->getPluginConfig("name_size_limit");
		}

		// Fail if content length exceeds the limit
		if (strlen($content) > $this->getPluginConfig("comment_size_limit")) {
			return 'Comment exceeds character limit: ' . strlen($content) . ' > ' . $this->getPluginConfig("comment_size_limit");
		}
        
        // path where the current page's comments are stored
        $path = $this->content_path . "/" . $this->id;

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        } else if (isset($reply_guid)) {
            $reply_exists = false;          // whether $reply_guid corresponds to a real comment. false by default
            $dir = glob($path . "/*.md"); 
            foreach ($dir as $file) {       // check if the comment pointed to by reply_guid exists, fail if not
                if (array_slice(explode("/", explode(".", $file)[0]), -1, 1)[0] == $reply_guid) {
                   $reply_exists = true;
                   break;
                }
            }
            if (!$reply_exists) {   // if the comment that reply_guid points to can't be found,
                return 'Unknown error when submitting reply';       // don't create the comment
            }
        }

        // build the comment file
        // this is hacky
        $file_contents = "---\n";
        $file_contents .= "guid: " . $guid . "\n";
        if (isset($reply_guid)) { $file_contents .= "reply_guid: " . $reply_guid . "\n"; }
        $file_contents .= "date: " . strval($date) . "\n";
        $file_contents .= "ip: " . strval($ip) . "\n";
        $file_contents .= "author: " . $author . "\n";
        // if comment review is enabled, add header
        if ($this->getPluginConfig("comment_review")) { $file_contents .= "pending: true\n"; }
        $file_contents .= "---\n";
        $file_contents .= $content;

        $handle = fopen($path . "/" . $guid . ".md", "w");
        if (!fwrite($handle, $file_contents)) { // if file writing fails for some reason
            fclose($handle);
            return 'Unknown error when submitting comment';
        }
        fclose($handle);
		
        return null;		// Success!
    }
	
	
	
	private function sendEmail($author, $content) {
		
		// User input sanitization
        $sanitized_author = strlen($author) != 0 ? filter_var($author, FILTER_SANITIZE_STRING) : null;
        $sanitized_content = strlen($content) != 0 ? htmlspecialchars($content, ENT_QUOTES, ini_get("default_charset")) : null;

		$to = $this->getPluginConfig('email_to');
		$from = $this->getPluginConfig('email_from');
		$subject = 'bigraccoon.ca - New Comment on: ' . $this->pico->getCurrentPage()['title'];
		$message = 'New comment by ' . $sanitized_author . ":\r\n\r\n" . $sanitized_content . "\r\n";
		$headers = array('From' => $from);		// PHP mail function needs the From value to be in the additional headers
					
		$success = mail($to, $subject, $message, $headers);		// Returns true on success, false on failure
		
		if (!$success) {
			return error_get_last()['message'];	// Roundabout way to find out what went wrong
		} else {
			return null;
		}
		
	}
	
	

    // return a nested array of hashes
    // [{"author":"me", "content":"hello", "replies":[{"author":"him", "content":"hi there"}]}]
    // replies to each comment are stored as a sub-array which can be recursed through in twig
    private function getComments() {
        $comments = []; // this is the dictionary where comment dictionaries will be stored

        $dir = glob($this->content_path . "/" . $this->id . "/*.md");

        // this loop reads comments from disk into memory as a dictionary
        // for each file in the content page dir:
        foreach ($dir as $file) {

            // read in the file
            try {
                // IP address left out to prevent leaks to users. it's for administrative use only i.e. blocking spam
                $headers = [
                    "GUID" => 'guid',
                    "Reply GUID" => 'reply_guid',
                    "Author" => 'author',
                    "Date" => 'date',
                    "Pending Review" => 'pending'
                ];
                
                // parse headers and content
                $meta = $this->parseFileMeta(file_get_contents($file), $headers);
                // Pico's parseFileContent function doesn't ignore frontmatter so I'm rolling my own here
                $content = explode("---\n", file_get_contents($file))[2]; 
            } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                error_log($e->getMessage());
                // continue to next loop iteration, we don't want improperly parsed comments to exist in the dictionary
                continue;
            }
            
            // build the dictionary entry
            if (!$meta['pending']) {  // if the comment is not pending review or has been approved
                $comment = [
                    'content' => $content
                ];
                foreach($meta as $key => $value) {
                    if (strlen($meta[$key]) > 0) {
                        $comment[$key] = $value;
                    }
                }
                
                // insert into dict
                if (strlen($meta['reply_guid']) > 0) {
                    $comments[$meta['reply_guid']][] = $comment;
                } else {
                    $comments[""][] = $comment;
                }
                
                // increment the comments counter
                $this->num_comments++;
            }
			
        }
        
        // this recursive function builds and sorts the child-pointer tree
        function insert_replies(&$array, &$comments) {
/*            if (!is_array($array) || !is_object($array))
                return; */
            foreach($array as &$comment) {
                // if this comment has children,
                if (isset($comments[$comment['guid']])) {
                    // recurse first so children are fully populated with their own descendants
                    insert_replies($comments[$comment['guid']], $comments);
                    // insert children into this comment's replies array
                    $comment['replies'] = $comments[$comment['guid']];
                    // sort the replies
                    usort($comment['replies'], function($a, $b) { return $b['date'] <=> $a['date']; });
                    // remove descendants from the top level
                    unset($comments[$comment['guid']]);
                }
            }
            
            // any replies that exist have been inserted
            return;
        }

        // build the tree
        insert_replies($comments[""], $comments);

        // remove the extra array wrapper around the top level
        $comments = $comments[""];
        // sort the top level
        if (!is_array($comments) && !is_object($comments))
           return;
        usort($comments, function($a, $b) { return $b['date'] <=> $a['date']; });
        return $comments;
    }



    public function onMetaParsed(array &$headers) {
        $this->headers = $headers;  // store the headers of the current page
    }



    public function onPageRendering(&$templateName, array &$twigVariables) {

        $this->id = $this->pico->getCurrentPage()['id'];
		
		if ($this->getPluginConfig("directory") !== null) {					// Set the path to the comments files if it's specified in pico's config
			$this->content_path = __DIR__ . '/../../' . $this->getPluginConfig("directory");
		} else {
			$this->content_path = __DIR__ . '/../../blog-comments';			// If it's not specified, use ths default
		}

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {        // if a comment is submitted
            if (isset($this->headers['comments']) && $this->headers['comments'] != 'true') {		// if comment submission is disabled on this page
                $twigVariables['comments_message'] = "Comment submission is disabled on this page"; // display error message and status 1
                $twigVariables['comments_message_status'] = 1;
                return;
            }
            
            // check if antispam honeypot is filled out
            if (isset($_POST['website']) && strlen($_POST['website']) > 0) {
                return;
            }
            
            $reply_guid = isset($_POST['comment_replyguid']) ? $_POST['comment_replyguid'] : null;  // set reply_guid to null if comment_replyguid is not included (i.e. if this comment is not a reply)
            
			// Submit the supplied form data.  If an error occurs, it will return a descriptive string; otherwise, null indicates success
			$result = $this->createComment($_POST['comment_author'], $_POST['comment_content'], $reply_guid);
			
			if ($result) {
				
				$twigVariables['comments_message'] = $result;				// display fail message and status 1
                $twigVariables['comments_message_status'] = 1;
				
			} else {
				
				$twigVariables['comments_message'] = "Comment submitted";	// display success message and status 0
                $twigVariables['comments_message_status'] = 0;
				
				// Send a mail notification about the new comment
				$mail_result = $this->sendEmail($_POST['comment_author'], $_POST['comment_content']);
				
				if ($mail_result) {
					error_log('Error submitting email notification for new comment: ' . $mail_result);
				}
				
			}


            $twigVariables['comments'] = $this->getComments() ?: "Server error";// display comments or fail, since we want to display comments after a new comment has been submitted to show the user their new comment
            $twigVariables['comments_number'] = $this->num_comments ?: "0";

        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {                      // if this is a GET request
            $twigVariables['comments'] = $this->getComments() ?: "Server error";// display comments or fail
            $twigVariables['comments_number'] = $this->num_comments ?: "0";
        }
    }
}