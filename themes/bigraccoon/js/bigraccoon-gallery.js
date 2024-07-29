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


// Lets start by saving an array of all thumbnail images, to refer back to whenever this page resizes.
const br_imageArray = document.getElementById("thumbnails").children;

// The max ratio can be adjusted arbitrarily to set a maximum for how much taller images can be made.  There may
// be cases where images are sufficiently wide that no more than one or two will fit in a row, and they could be
// doubled in height or more (which you may or may not like).  If they would grow by more than this maximum ratio,
// they will instead have their top and bottom *cropped off* until they fill the row width when grown by this ratio.
// If you never want that to happen, set this to a very high value such as 100.
const br_maxRatio = 1.5;


function br_adjustThumbnailSizes() {
	
	// We're not going to do anything if there are no images to act on
	if (br_imageArray.length > 0) {
	
		// This will run on every page resize, so we need to get the page width anew
		const pageRect = document.getElementById("thumbnails").getBoundingClientRect();
		const pageWidth = pageRect.width - 4;	// Subtract 4 pixels because this value is somehow too wide on my phone browser
		
		//document.getElementById("debug").innerHTML += `<p>pageWidth: ${pageWidth}</p>`;
		
		// We need to know the default height of the thumbnails, based on the page CSS.  It's possible that
		// value could change as the page is resized, so we can't just save it on page load and assume it will
		// stay true.  So we need to quickly remove our custom width and height from a thumbnail, measure its
		// height under normal styling, then revert back to the modified dimensions which are about to be recalculated.
		// Kinda clunky!  But way faster than hunting through the CSS and converting whatever units it uses to px.
		const currentImgRect = br_imageArray[0].children[0].getBoundingClientRect();
		const currentWidth = currentImgRect.width;
		const currentHeight = currentImgRect.height;
		
		br_imageArray[0].children[0].style.width = "";
		br_imageArray[0].children[0].style.height = "";
		
		const originalHeight = br_imageArray[0].children[0].getBoundingClientRect().height;
		
		br_imageArray[0].children[0].style.width = currentWidth;
		br_imageArray[0].children[0].style.height = currentHeight;


		// Although we're deaing with the size of <img> elements, they are within <a> tags,
		// and the <a> tags have margins and borders which will create a gap between images.
		// Get the current style values of the side margins and side borders of the <a> tags
		const marginLeft = window.getComputedStyle(br_imageArray[0]).marginLeft.match(/\d+/);	// Returns a string like "2px",
		const marginRight = window.getComputedStyle(br_imageArray[0]).marginLeft.match(/\d+/);	// so .match(/\d+/) extracts just the digits
		const borderLeft = window.getComputedStyle(br_imageArray[0]).borderLeft.match(/\d+/);
		const borderRight = window.getComputedStyle(br_imageArray[0]).borderRight.match(/\d+/);

		// Add them all together to get the size of the gap between images.  parseInt() is here because these variables are all strings
		const gapWidth = parseInt(marginLeft) + parseInt(marginRight) + parseInt(borderLeft) + parseInt(borderRight);
		
		//document.getElementById("debug").innerHTML += `<p>gapWidth: ${gapWidth}</p>`;
		
		// Initialize our rows of thumbnails
		var rowArray = [[],];		// Each row of thumbnails on the screen (and the first row is a new array)
		var rowWidthArray = [0,];	// The width of each row (in pixels), with the first one beginning at zero
		var rowIndex = 0;			// We will start working at row 0

		// Step through each thumbnail image on this page
		for (var i = 0; i < br_imageArray.length; i++) {
			
			// Undo any cropping, which would mess up measurements of the image dimensions
			br_imageArray[i].children[0].classList.remove("cropped");
			br_imageArray[i].children[0].style.width = "auto";

			const currentRect = br_imageArray[i].children[0].getBoundingClientRect();		// Current size of the image
			const imageWidth = currentRect.width * (originalHeight / currentRect.height);	// Original width of this image

			// Check if this image's width will fit in the current row
			if (rowWidthArray[rowIndex] + imageWidth + gapWidth > pageWidth) {
				
				// Adding this image to the row will overflow off the page, so we need to start the next row
				rowIndex++;
				rowArray[rowIndex] = [];
				rowWidthArray[rowIndex] = 0;
			}
			
			// Add this image to the current row
			rowArray[rowIndex].push(br_imageArray[i]);
			
			// Add this image's width (and margins) to the current row's total width
			rowWidthArray[rowIndex] += imageWidth + gapWidth;

		}
		
		
		// Now work on each row, resizing them to fill the full page width
		for (var r = 0; r < rowArray.length; r++) {
			
			// We want to change the height of the row so its width fills the page width.
			// We will apply that height to each image in the row.  But the gaps between images are going to remain static.
			// So before calculating, we remove all gaps from the respective widths
			const numGaps = rowArray[r].length;
			const ratio = (pageWidth - numGaps * gapWidth) / (rowWidthArray[r] - numGaps * gapWidth);

			// Calculate the new height for each image in this row
			const newHeight = Math.floor(originalHeight * ratio);	// Round the height down to ensure the row isn't too wide by a fraction of a pixel

			//document.getElementById("debug").innerHTML += `<p>Row: ${r} | rowWidth: ${rowWidthArray[r]} | numGaps: ${numGaps} | ratio: ${ratio} | new width: ${(rowWidthArray[r] - numGaps * gapWidth) * ratio + numGaps * gapWidth} | new height: ${newHeight}</p>`;

			// Now apply that height to each image in this row
			for (var i = 0; i < rowArray[r].length; i++) {
					rowArray[r][i].children[0].style.height = newHeight + "px";
			}

			// If that ratio was above the chosen maximum, crop the top and bottom of each newly-sized image so their
			// height is no greater than the maximum -- but not if it's the final row because it may only have 1 or 2 images
			if (ratio > br_maxRatio) {
				if (r == rowArray.length - 1) {
					
					for (var i = 0; i < rowArray[r].length; i++) {
						rowArray[r][i].children[0].style.height = originalHeight + "px";
					}

				} else {

					const maxHeight = Math.floor(originalHeight * br_maxRatio);
					
					for (var i = 0; i < rowArray[r].length; i++) {
						rowArray[r][i].children[0].classList.add("cropped");
						
						// It seems silly to set an image's width to it current width, but this overrides the existing "width: auto" style
						rowArray[r][i].children[0].style.width = rowArray[r][i].children[0].getBoundingClientRect().width + "px";
						rowArray[r][i].children[0].style.height = maxHeight + "px";
					}
					
				}
			}

		}
		
		// TROUBLESHOOTING
/*		for (var r = 0; r < rowArray.length; r++) {
			const numGaps = rowArray[r].length;

			var newWidth = 0;
			
			for (var i = 0; i < rowArray[r].length; i++) {
				const imageRect = rowArray[r][i].children[0].getBoundingClientRect();
				newWidth += imageRect.width + gapWidth;
			}

			console.log(`Row: ${r} | newWidth: ${newWidth}`);
		}

		console.log(`pageWidth: ${pageWidth}`);
*/
	}
	
}

/*
function br_getOriginalThumbnailHeight() {
	
	//console.log("DOMContentLoaded!");

	br_imageArray = document.getElementById("thumbnails").children;				// Actually an array of <a> elements, each containing an <img>
	const br_imageRect = br_imageArray[0].children[0].getBoundingClientRect();	// We'll take the dimensions from the first image
	originalHeight = br_imageRect.height;									// Assuming they all start with the same height

	//console.log(`originalHeight: ${originalHeight}`);

	// Now perform the resizing for the first time
	br_adjustThumbnailSizes();
}
*/

// If the page hasn't finished loading yet, add an event listener.  If it has finished loading, just run the function immediately.
if (document.readyState === "loading") {
	window.addEventListener("load", br_adjustThumbnailSizes);
} else {
	br_adjustThumbnailSizes();
}

window.addEventListener("resize", br_adjustThumbnailSizes);
