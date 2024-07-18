* Test site in Chrome, Safari
* Make BG image fade in sooner
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
	* Default is white, overridden by system preference (reported by browser)
	* If it's customized, how can it persist across browser sessions?  Cookie?
* Replace fontawesome icons with SVG code
* Get rid of jQuery
	* Figure out how that "scrolly" thing works to smoothly move between points on the page
* Introduce a highlight colour, or make tinted instead of plain black?
	* Brownish (like a raccoon)?
	* Or the existing bright blue could be more prominent
* Rebuild gallery in Pico
	* Add tags
	* Centre the text on image pages
	* Add metadata box (size, tags, etc.) below image (collapsible?)
	* Any good way to count pageviews?
	* Pre-cache next and previous images
	* Figure out 18+ images
		* Include placeholders in the default view?  That requires a static image file, eg. a blurred copy (CSS filtering is easily bypassed)
		* Or make a very obvious 18+ toggle?
		* Once opted-in to 18+ images, how to store that to last between pages?
			* Cookie
			* PHP session
			* HTTP GET parameter in URL
			* Pico user accounts plugin
		* If someone opens a link to an 18+ image, how to redirect them to the opt-in method?
	* Background images:
		* Same image as the page you're viewing? (Need to use a small size to preserve bandwidth)
		* A blurred rendition of the image (eg. a colour value matrix)?
		* A solid colour, derived somehow from the image?
		* A common background across all pages?
	* Fix bar of index page numbers
		* Padding is weird
		* Need to eliminate some when screen is narrow, replace with ellipses
* Clean up/refactor CSS because it's probably 50% unused cruft
