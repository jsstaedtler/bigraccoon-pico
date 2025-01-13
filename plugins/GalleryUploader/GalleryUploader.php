<?php

/**
 * Gallery Uploader for Pico CMS (@see https://github.com/picocms/Pico)
 *
 * @author JS Staedtler
 * @link https://bigraccoon.ca
 * @repository https://github.com/jsstaedtler
 * @license http://opensource.org/licenses/MIT
 */

class GalleryUploader extends AbstractPicoPlugin
{
    const API_VERSION = 3;
    protected $dependsOn = array();
    protected $enabled = true;      // whether this plugin is enabled by default sitewide
    protected $content_path = __DIR__ . '/../../content/gallery';
	protected $assets_path = __DIR__ . '/../../assets/gallery';


    public function onPageRendering(&$templateName, array &$twigVariables) {

		// Check if the page is responding to a POST request from a GalleryUploader form
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_name']) && $_POST['form_name'] == 'GalleryUploader') {
			
			$twigVariables['upload_error'] = '';


			if (!isset($_POST['title']) || mb_strlen($_POST['title'], 'utf8') == 0) {
				$twigVariables['upload_error'] = 'No title provided';
                return;
            }

            if (!isset($_POST['md-filename']) || mb_strlen($_POST['md-filename'], 'utf8') == 0) {
				$twigVariables['upload_error'] = 'No md filename provided';
                return;
            }
			
			if (!isset($_FILES['media-picker']) || $_FILES['media-picker']['size'] == 0) {
				$twigVariables['upload_error'] = 'No media file provided (or upload failed)';
                return;
			}
			
			if (
				!isset($_POST['year']) || !isset($_POST['month']) || !isset($_POST['day']) || !isset($_POST['hour']) || !isset($_POST['minute'])
				|| mb_strlen($_POST['year'], 'utf8') != 4
				|| mb_strlen($_POST['month'], 'utf8') != 2
				|| mb_strlen($_POST['day'], 'utf8') != 2
				|| mb_strlen($_POST['hour'], 'utf8') != 2
				|| mb_strlen($_POST['minute'], 'utf8') != 2
			) {
				$twigVariables['upload_error'] = 'Incorrect date value(s) submitted';
                return;
			}
			
			$md_filename = $_POST['md-filename'] . '.md';
			preg_match('/\.[^\.]+$/i', $_FILES['media-picker']['name'], $result);
			$media_extension = $result[0];
			$media_filename = $_POST['md-filename'] . $media_extension;
			
			// Move the uploaded file into the assets folder with the proper filename
			if (!move_uploaded_file($_FILES['media-picker']['tmp_name'], $this->assets_path . '/' . $media_filename)) {
				$twigVariables['upload_error'] = 'Error moving media file to destination: ' . $this->assets_path . '/' . $media_filename;
                return;
			}
			
			
			$tags = isset($_POST['selected-tags']) ? json_decode($_POST['selected-tags']) : array();
			
			
			// Now we build an md file.  We don't need to test every field if it's populated, since empty YAML values are fine.
			$md_contents = "---\n";
			
			// HTML date field outputs ISO "yyyy-mm-ddThh:mm" format, but Pico wants "yyyy-mm-dd hh:mm".  Pretty simple to swap out the "T".
			$md_contents .= "date: " . $_POST['year'] . "-" . $_POST['month'] . "-" . $_POST['day'] . " " . $_POST['hour'] . ":" . $_POST['minute'] . "\n";
			// When a YAML string value might possibly contain a colon, it must be wrapped in quotation marks, and existing quotes in the string must be escaped
			$md_contents .= "title: \"" . str_replace('"', '\"', $_POST['title']) . "\"\n";
			$md_contents .= "image: gallery/" . $media_filename . "\n";
			$md_contents .= "imagedescription: \"" . (isset($_POST['media-description']) ? str_replace('"', '\"', $_POST['media-description']) : '') . "\"\n";
			if (isset($_POST['video'])) $md_contents .= "isvideo: true\n";
			$md_contents .= "width: " . $_POST['media-width'] . "\n";
			$md_contents .= "height: " . $_POST['media-height'] . "\n";
			$md_contents .= "adultonly: " . (isset($_POST['adult-only']) ? "true" : "false") . "\n";
			$md_contents .= "tags: \n";
			foreach($tags as $tag) {
				$md_contents .= " - \"" . $tag . "\"\n";
			}
			if (isset($_POST['related-images']) && (trim($_POST['related-images']) != '')) {
				$md_contents .= "related: \n";
				foreach(explode("\n", $_POST['related-images']) as $line) {
					$md_contents .= " - " . trim($line) . "\n";
				}
			}
			$md_contents .= "hidden: " . (isset($_POST['page-hidden']) ? "true" : "false") . "\n";
			
			$md_contents .= "template: gallery-image\n---\n\n";		// That's it for frontmatter
			
			$md_contents .= $_POST['text-content'] . "\n";
			
			
			$handle = fopen($this->content_path . '/' . $md_filename, "w");
			if (!fwrite($handle, $md_contents)) {					// If file writing fails for some reason
				fclose($handle);
				$twigVariables['upload_error'] = 'Error writing md file to destination: ' . $this->content_path . '/' . $md_filename . '(but media file succeeded)';
				return;
			}
			fclose($handle);
			
		}
		
    }
}
