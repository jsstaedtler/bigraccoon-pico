* Test site in Chrome, Safari
* Make BG image fade in sooner?
* Add OpenGraph meta tags to all pages (pulling data programmatically)
* Store all fonts locally
	* Pick better header/body fonts?
* Move all icons/logos to theme folder (instead of in "assets")
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
* Replace inline SVG code with SVG files that have no "fill" attribute (CSS will provide that)
* Get rid of jQuery
* Introduce a highlight colour, or make tinted instead of plain black?
	* Brownish (like a raccoon)?
	* Or the existing bright blue could be more prominent
* Rebuild gallery in Pico
    * style.css line 1717 is for gallery-index pagination, but it's messing up article/news pagination
	* Submitting a comment shuld preserve the URL parameters
	* Make "Medium: Pencil/Digital" tags
	* Find out how piwigo can resize images so cleanly
	* Any good way to count pageviews?
	* Make better 18+ icon for thumbnails
	* Add number of images to the "group" icon
	* Fix padding of gallery index page bar (when squished on mobile)
	* "moreimages" YAML key ought to be renamed to "variants"
* Clean up/refactor CSS because it's probably 50% unused cruft
