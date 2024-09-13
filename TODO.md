* <ol> numbering resets to "1" regardless of what number you put in the md file - see the dryer article
* Make new class of imgblock div (class="imgblock single") that is 100% wide, for single images
* Test site in Chrome, Safari
* Make BG image fade in sooner
* Add OpenGraph meta tags to all pages (pulling data programmatically)
* Store all fonts locally
	* Pick a better fixed-width font
* Add other sites to Links section
        * Make a new post, to appear after the existing Links content
        * Provide a link banner/buttons
* Somehow reset page top periodically, so the navbar doesn't stick in the wrong places?
	* Or reset when scrolling back to the top?
* Maybe instead of back-to-top button linking to an ID, have it scroll up until the navbar unsticks
* Lazy-loading of image thumbnails
* Lightbox improvements:
	* Display caption/alt text at bottom of lightbox
	* Tap image to hide controls, ta again to bring them back
	* Centre the zoom effect on the mouse cursor, or the touch/pinch point
* Show reduced-size image thumbnails on pages, but always open full-size in the Lightbox
	* How to programatically determine what image size is needed for thumbnails?
	* Just use the native HTML implementation?
* Display imgblock groups 4-in-a-row on very wide screens?
* With JS disabled, can images link to their full-size version in a new tab?
	* Will probably need a Pico plugin or modification to Markdown parsing
* Add dark-mode toggle
* Replace fontawesome icons with SVG code
* Get rid of jQuery
	* Figure out how that "scrolly" thing works to smoothly move between points on the page
* Add comments
	* Or just reaction buttons?
* Introduce a highlight colour, or make tinted instead of plain black?
	* Brownish (like a raccoon)?
	* Or the existing bright blue could be more prominent
* Rebuild gallery in Pico
	* Background images:
		* Same image as the page you're viewing? (Need to use a small size to preserve bandwidth)
		* A blurred rendition of the image (eg. a colour value matrix)?
		* A solid colour, derived somehow from the image?
		* A common background across all pages?
