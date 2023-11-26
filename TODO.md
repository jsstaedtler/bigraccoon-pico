#### To complete before release
* JS on page load to see if checkboxes are checked, and if so, to activate their feature (eg. text style, spoiler reveal)
* Test site with JS disabled
* Test site in Chrome, Safari, Edge, IE(?)
    * Chrome mobile: article listing bullets are misaligned
* Get rid of underline in article listing bylines
* Black home button not appearing on mobile navbar
* Make BG image fade in sooner
* Make 404 page
* Piwigo
	* Adjust page header to integrate it better with the Pico site
	
#### After release
* Store all fonts locally
* Somehow reset page top periodically, so the navbar doesn't stick in the wrong places?
	* Or reset when scrolling back to the top?
* In the pagination bar, add greyed-out buttons at the start/end so it looks better balanced
* On mobile, close menu after clicking back-to-top button
	* Need to make this execute, *and* still keep scrolling to the top
* Click images to zoom in on them in a lightbox view
* Add dark-mode toggle
* Replace fontawesome icons with SVG code
* Get rid of jQuery
	* Figure out how that "scrolly" thing works to smoothly move between points on the page
* Split <HEAD> of templates into reusable segment
	* Twig renders the segment before inserting, which makes no sense for <HEAD> content, so browser rendering goes haywire
* Add comments using Mastodon
* Does main logo at the top of each page, or the copyright footer, need a border or dropshadow?
	* Dropshadow doesn't work well on transparent imgs, may need background-image and filter trickery
	* Or, simpler, keep the background tinted dark enough at the top of the page
	* Need to test with a pure white background image
* Introduce a highlight colour, or make tinted instead of plain black?  Brownish (like a raccoon)?
* Rebuild gallery in Pico
	* Background images:
		* Same image as the page you're viewing?
		* A blurred rendition of the image (eg. a colour value matrix)?
		* A solid colour, derived somehow from the image?
		* A common background across all pages?
