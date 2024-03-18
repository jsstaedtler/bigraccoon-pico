/* bigraccoon.ca - Gallery code for making a masonry layout from a list of image thumbnails
 *
 * The gallery index page contains an arbitrary number of thumbnail images.
 * Each <img> is wrapped in an <a> tag, and all of them are within <div id="thumbnails">.
 * The div uses "display: flex", with "flex-wrap: wrap" so that they automatically fall into multiple rows.
 * The div also uses "justify-content: space-between" so the rows fill the full width of the page,
 * but with unpredictably wide gaps between thumbnails.
 *
 * We can increase the height of each row until the gaps in that row shrink to the desired minimum width,
 * creating a brick-like "masonry" effect.  The challenge is to programmatically determine which images
 * lie together in each row, and how much taller they must be so their combined width fills the page.
 *
 * The operation will be done on initial page load, but also whenever the page is resized.
 */

// Before doing anything, we need to get the original height of all thumbnail images (as defined by normal styling).
// To do so, we need to get an array of all thumbnail images, so we might as well save it globally for reuse.
// These will be populated on page load by br_getOriginalThumbnailHeight().
var br_imageArray;
var br_originalHeight;


function br_adjustThumbnailSizes() {
	
	// We're not going to do anything if there are no images to act on
	if (br_imageArray.length > 0) {
	
		// This will run on every page resize, so we need to get the page width anew
		const pageRect = document.getElementById("thumbnails").getBoundingClientRect();
		const pageWidth = pageRect.width;

		// To discover the margins around a thumbnail image, we subtract its width from the width of its parent <a> element
		// We know there's at least one image to work on, and they all have identical margins, we'll do the calculation on the first image
		const aRect = br_imageArray[0].getBoundingClientRect();					// Use this instead of any ".*width" properties, because it returns accurate, fractional values
		const iRect = br_imageArray[0].children[0].getBoundingClientRect();		// Can't use br_imageRect here because the image size may have changed
		const imageMargin = (aRect.width - iRect.width) / 2;					// This is the margin on one side of an image (or the average of left and right)
		
		var rowArray = [[],];		// Each row of thumbnails on the screen (and the first row is a new array)
		var rowWidthArray = [0,];	// The width of each row (in pixels), with the first one beginning at zero
		var rowIndex = 0;			// We will start working at row 0

		// Step through each thumbnail image on this page
		for (var i = 0; i < br_imageArray.length; i++) {
			
			const currentRect = br_imageArray[i].children[0].getBoundingClientRect();			// Current size of the image
			const imageWidth = currentRect.width * (br_originalHeight / currentRect.height);	// Original width of this image

			// Check if this image's width will fit in the current row
			if (rowWidthArray[rowIndex] + imageWidth + imageMargin * 2 > pageWidth) {
				
				// Adding this image to the row will overflow off the page, so we need to start the next row
				rowIndex++;
				rowArray[rowIndex] = [];
				rowWidthArray[rowIndex] = 0;
			}
			
			// Add this image to the current row
			rowArray[rowIndex].push(br_imageArray[i]);
			
			// Add this image's width (and margins) to the current row's total width
			rowWidthArray[rowIndex] += imageWidth + imageMargin * 2;
			
			//console.log(`Image: ${i} | Current row: ${rowIndex} | imageWidth: ${imageWidth} | Current rowWidth: ${rowWidthArray[rowIndex]} | Current row length: ${rowArray[rowIndex].length}`);

		}
		
		// Now work on each row, resizing them to fill the full page width
		for (var r = 0; r < rowArray.length; r++) {
			
			// We want to change the height of the row so its width fills the page width.
			// We will apply that height to each image in the row.  But the margins are going to remain static.
			// So before calculating, we remove all margins from the respective widths (and remember there are none on the far left and right)
			const numMargins = rowArray[r].length/* - 1*/;
			const ratio = (pageWidth - numMargins * imageMargin * 2) / (rowWidthArray[r] - numMargins * imageMargin * 2);
			
			//console.log(`Row: ${r} | rowWidth: ${rowWidthArray[r]} | numMargins: ${numMargins} | ratio: ${ratio}`);

			// Now apply that height to each image in this row
			for (var i = 0; i < rowArray[r].length; i++) {
				const newHeight = Math.floor(br_originalHeight * ratio);	// Round the height down to ensure the row isn't too wide by a fraction of a pixel
				rowArray[r][i].children[0].style.height = newHeight + "px";
				//rowArray[r][i].children[0].setAttribute("height", newHeight+"")
			}

		}
		
		// TROUBLESHOOTING
		for (var r = 0; r < rowArray.length; r++) {
			const numMargins = rowArray[r].length - 1;
			const ratio = (pageWidth) / (rowWidthArray[r]);

			var newWidth = 0 - imageMargin * 2;
			
			for (var i = 0; i < rowArray[r].length; i++) {
				const imageRect = rowArray[r][i].children[0].getBoundingClientRect();
				newWidth += imageRect.width + imageMargin * 2;
			}
			
			//console.log(`Row: ${r} | newWidth: ${newWidth}`);
		}
		
		//console.log(`pageWidth: ${pageWidth}`);
		
	}
	
}


function br_getOriginalThumbnailHeight() {
	
	//console.log("DOMContentLoaded!");

	br_imageArray = document.getElementById("thumbnails").children;				// Actually an array of <a> elements, each containing an <img>
	const br_imageRect = br_imageArray[0].children[0].getBoundingClientRect();	// We'll take the dimensions from the first image
	br_originalHeight = br_imageRect.height;									// Assuming they all start with the same height

	//console.log(`br_originalHeight: ${br_originalHeight}`);

	// Now perform the resizing for the first time
	br_adjustThumbnailSizes();
}


// If the page hasn't finished loading yet, add an event listener.  If it has finished loading, just run the function immediately.
if (document.readyState === "loading") {
	window.addEventListener("load", br_getOriginalThumbnailHeight);
} else {
	br_getOriginalThumbnailHeight();
}

window.addEventListener("resize", br_adjustThumbnailSizes);
