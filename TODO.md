* Disable Pico pages[] loading on gallery-image templates to reduce load times; only load previous and next pages (if it's even possible--refer to the plugin that prevents page tree processing)
	* Maybe some way to only load pages within the current section, instead of all sections?
* Fix double-slash in URI issue (pico.php)
	* To replace multiple slashes with a single one: $uri = preg_replace('(\/{2,})', '/', $uri);
* Uploader: Escape quotation marks in the description field
* Add a "share" button (next to "like" button) which at least copies the URL to clipboard
* OpenGraph meta image needs to be of reduced dimension, and pixelated for 18+ content
* Put an "outside site" icon on links to other domains
	* May need a Pico plugin to find \<a> elements, check if they're to an outside domain, and add a class so CSS can style it
* Move "bg" images out of their own folder and put them with the rest of the assets (maybe as "bg.jpg")
* Modify PicoTags so it can be limited to a specific section instead of the entire site
* View counter
	* Save IP and agent string for each page view
	* Keep just one instance of each IP/agent pair, which will result in counting only unique views of each page
	* Save referrers to each page ID?  Perhaps only external ones?  (Exclude bigraccoon.ca itself from being recorded)
	* Reverse DNS lookup of IPs
* Quick feedback buttons
	* Put a heart ("like") button on each gallery image page (perhaps on each variant)
	* Display on each thumbnail in the gallery index
	* Make a plugin that could permit any number of custom reactions (eg. thumbs up/down, reaction emojis); provide variables that user can use in Twig files, and they will provide their own names and icons in HTML/CSS
* Comments
	* Change "author_*" values to "owner_*"
	* Set up new config options, including enable_replies
	* Send email to user when their comment is approved or if they get a reply
	* Include link in that email to delete a comment
	* Remember to PHP-validate the provided email address
	* Include notice that if the email recipient did not make the comment, they can request their address be denylisted
* Add dark-mode toggle
	* Default will be light mode, overridden by system preference (reported by browser)
	* If it's customized, how can it persist across browser sessions?  Cookie?
* Gallery improvements
	* Submitting a comment and showing adult-only content are losing the URL parameters for tags
	* Organize tags into sections (OCs, styles, years, etc.), instead of a mixed up cloud
	* Make "Media: Pencil/Digital" tags
	* Add number of images to the "variants" thumbnail icon
	* Add icon with number of comments (if > 0)
	* Need a minimum thumbnail width to accomodate all the icons
	* Fix padding of gallery index page bar (when squished on mobile)
	* Thumbnails width and height attributes (to make less jumpy layout on page load)
		* Can PHP take the dimensions saved in the md file and calculate what they are when resized to thumbnails?
	* Save "centre of interest" for each image, when thumbnails are forced to crop
* Provide separate RSS feeds per section (eg. Gallery, Articles)
* Make new class of imgblock div (class="imgblock single") that is 100% wide, for single images
* Test site in Chrome, Safari
* Make BG image fade in sooner?
* Store all fonts locally
	* Pick better header/body fonts?
* Add other sites to Links section
	* Make a new post, to appear after the existing Links content
	* Provide a link banner/buttons
* Somehow reset page top periodically, so the navbar doesn't stick in the wrong places?
	* Or reset when scrolling back to the top?
* Maybe instead of back-to-top button linking to an ID, have it scroll up until the navbar unsticks
* Lazy-loading of down-page images (does that delay the display of the background image?)
* Lightbox improvements:
	* Fine-tune the pinch-to-zoom sensitivity
	* Display caption/alt text at bottom of lightbox
	* Tap image to hide controls, tap again to bring them back
	* Centre the zoom effect on the mouse cursor, or the touch/pinch point
	* Fix buttons to be styled like gallery thumbnail icons
	* Show reduced-size image thumbnails on pages, but always open full-size in the Lightbox
		* How to programatically determine what image size is needed for thumbnails?
		* Just use the native HTML implementation?
* Display imgblock groups 4-in-a-row on very wide screens?
* With JS disabled, can images link to their full-size version in a new tab?
	* Will probably need a Pico plugin or modification to Markdown parsing
* Replace fontawesome icons with SVGs
* Introduce a highlight colour, or make tinted instead of plain black?
	* Brownish (like a raccoon)?
	* Or the existing bright blue could be more prominent
* Clean up/refactor CSS because it's probably 50% unused cruft
