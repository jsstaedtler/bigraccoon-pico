* Move "bg" images out of their own folder and put them with the rest of the assets (maybe as "bg.jpg")
* Modify PicoTags so it can be limited to a specific section instead of the entire site
* View counter
  * Add IP address filtering
  * Build a template to view stats for daily/monthly/etc.
  * Use PicoUsers to secure the stats pages
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
	* Show reduced-size image thumbnails on pages, but always open full-size in the Lightbox
		* How to programatically determine what image size is needed for thumbnails?
		* Just use the native HTML implementation?
* Display imgblock groups 4-in-a-row on very wide screens?
* With JS disabled, can images link to their full-size version in a new tab?
	* Will probably need a Pico plugin or modification to Markdown parsing
* Add dark-mode toggle
	* Default will be light mode, overridden by system preference (reported by browser)
	* If it's customized, how can it persist across browser sessions?  Cookie?
* Replace fontawesome icons with SVGs
* Get rid of jQuery
* Introduce a highlight colour, or make tinted instead of plain black?
	* Brownish (like a raccoon)?
	* Or the existing bright blue could be more prominent
* Rebuild gallery in Pico
	* Submitting a comment and showing adult-only content are losing the the URL parameters for tags
	* Pages 8 and 10 have messed up row lengths
	* Make "Media: Pencil/Digital" tags
	* Make better 18+ icon for thumbnails
	* Add number of images to the "group" icon
	* Fix padding of gallery index page bar (when squished on mobile)
	* "moreimages" YAML key ought to be renamed to "variants"
* Clean up/refactor CSS because it's probably 50% unused cruft
