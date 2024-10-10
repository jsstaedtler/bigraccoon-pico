* Uploader: Escape quotation marks in the description field
* Move "bg" images out of their own folder and put them with the rest of the assets (maybe as "bg.jpg")
* Modify PicoTags so it can be limited to a specific section instead of the entire site
* View counter
  * Build a template to view stats for daily/monthly/etc.
  * Use PicoUsers to secure the stats pages
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
	* "moreimages" YAML key ought to be renamed to "variants"
	* Thumbnails width and height attributes (to make less jumpy layout on page load)
		* Can PHP take the dimensions saved in the md file and calculate what they are when resized to thumbnails?
	* Save "centre of interest" for each image, when thumbnails are forced to crop
* Provide separate RSS feeds per section (eg. Gallery, Articles)
* "ol" numbering resets to "1" regardless of what number you put in the md file - see the dryer article
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
