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

// Debugging!  Change this to true to see lots of numbers get dumped to the bottom of the page
const debug = false;

function debugMsg(par, type, txt = null)
{
	var el = document.createElement(type);
	if (debug) {
		if (txt) el.innerHTML = txt;
		par.appendChild(el);
	}
	return el;
}

const debugDiv = debugMsg(document.getElementsByClassName('post')[0], 'div');



// Lets start by saving an array of all thumbnail images, to refer back to whenever this page resizes.
// Note that images will be wrapped in tags like <a>, <abbr>, and possibly more.  So use ".getElementsByTagName('img')[0]"
// on each element of this array to get the actual image object.
var br_imageArray;
try {
	br_imageArray = document.getElementById('thumbnails').children;
} catch {
	// There probably weren't any thumbnails (eg. they're all filtered out by tag selections)
	br_imageArray = [];
}

// Create an object containing the current CSS styling of one thumbnail child (which updates automatically).
// This is used only to see what border and margin widths currently are.
var thumbnailChildStyle;
try {
	thumbnailChildStyle = window.getComputedStyle(br_imageArray[0]);
} catch {
	thumbnailChildStyle = null;
}

// The max ratio can be adjusted arbitrarily to set a maximum for how much taller images can be made.  There may
// be cases where images are sufficiently wide that no more than one or two will fit in a row, and they could be
// doubled in height or more (which you may or may not like).  If they would grow by more than this maximum ratio,
// they will instead have their top and bottom *cropped off* until they fill the row width when grown by this ratio.
// If you never want that to happen, set this to a very high value such as 100.
const br_maxRatio = 1.5;

// You can specify a minimum width (in pixels) for every thumbnail.  This is useful when overlaying icons on top of them,
// to ensure there's enough space; but also vital if you have images that are extremely tall and thin.  If a thumbnail is
// narrower than this width (*before* growing to fill the row), it will be given this width and be set to crop off the
// top and bottom to fit.  Set to 0 for there to be no minimum.
const br_minWidth = 60;


function br_adjustThumbnailSizes() {
	
	// We're not going to do anything if there are no images to act on
	if (br_imageArray.length > 0) {
		
		/**/debugMsg(debugDiv, 'p', `Number of images: ${br_imageArray.length}`);
	
		// This will run on every page resize, so we need to get the page width anew
		const pageRect = document.getElementById('thumbnails').getBoundingClientRect();
		const pageWidth = Math.floor(pageRect.width) - 1;	// Round down to an integer value, and subtract 1 pixel to be sure we won't overflow
		
		/**/debugMsg(debugDiv, 'p', `#thumbnails pageRect.width: ${pageRect.width}`);
		/**/debugMsg(debugDiv, 'p', `#thumbnails pageWidth: ${pageWidth}`);
		
		// We need to know the default height of the thumbnails, based on the page CSS.  It's possible that
		// value could change as the page is resized, so we can't just save it on page load and assume it will
		// stay true.  So we need to remove any custom width and height already applied to a thumbnail, then 
		// measure its height under default styling.  Take note of the ".height" property (which returns the
		// actual current height of an image), versus ".style.height" (the inline CSS style on the image).
		br_imageArray[0].getElementsByTagName('img')[0].style.width = "";
		br_imageArray[0].getElementsByTagName('img')[0].style.height = "";

		const originalHeight = br_imageArray[0].getElementsByTagName('img')[0].height;
		const maxHeight = originalHeight * br_maxRatio;
		/**/debugMsg(debugDiv, 'p', `img originalHeight: ${originalHeight}`);
		/**/debugMsg(debugDiv, 'p', `maxHeight: ${maxHeight}`);


		// Each thumbnail image is wrapped in an <a> element, which has a border around it, as well as a margin.  Those add up to a specific
		// distance between each image and the next image in the same row.  When a row of images is grown to fill the available page width,
		// the images will grow wider but those gaps will *not*.  So to correctly calculate how much a row should grow, we must learn what
		// the gap width is:
		const marginLeft = thumbnailChildStyle.getPropertyValue('margin-left').replace('px', '');		// Returns a string like "2.5px",
		const marginRight = thumbnailChildStyle.getPropertyValue('margin-right').replace('px', '');		// so use .replace() to return just the digits
		const borderLeft = thumbnailChildStyle.getPropertyValue('border-left-width').replace('px', '');
		const borderRight = thumbnailChildStyle.getPropertyValue('border-right-width').replace('px', '');

		/**/debugMsg(debugDiv, 'p', `#thumbnails child marginLeft: ${marginLeft}`);
		/**/debugMsg(debugDiv, 'p', `#thumbnails child marginRight: ${marginRight}`);
		/**/debugMsg(debugDiv, 'p', `#thumbnails child borderLeft: ${borderLeft}`);
		/**/debugMsg(debugDiv, 'p', `#thumbnails child borderRight: ${borderRight}`);

		// Add them all together to get the width of the gap between images.  parseFloat() is here because these variables are all strings.
		const gapWidth = parseFloat(marginLeft) + parseFloat(marginRight) + parseFloat(borderLeft) + parseFloat(borderRight);		
		/**/debugMsg(debugDiv, 'p', `gapWidth: ${gapWidth}`);

		// Initialize our rows of thumbnails
		var rowArray = [[],];		// Each row of thumbnails on the screen (and the first row is a new array)
		var rowWidthArray = [0,];	// The width of each row (in pixels), with the first one beginning at zero
		var rowIndex = 0;			// We will start working at row 0

		/**/var debugUL = debugMsg(debugDiv, 'ul');

		// Step through each thumbnail image on this page
		for (var i = 0; i < br_imageArray.length; i++) {
						
			// Get the current <img> element from within the parent thumbnail elements
			const currentImg = br_imageArray[i].getElementsByTagName('img')[0];
			
			// Reset to original size and undo any cropping, which could mess up our measurement of the image dimensions
			// ***** DO I NEED TO ADJUST FOR CENSORED IMAGES THAT ARE ZOOMED IN WITH CSS *****
			currentImg.style.width = "";
			currentImg.style.height = "";
			currentImg.classList.remove("cropped");

			// Get the width of this image (in pixels), or the minimum thumbnail width if it's less than that
			const imageWidth = (currentImg.width >= br_minWidth) ? currentImg.width : br_minWidth;

			// Check if this image's width will *not* fit in the current row
			if (rowWidthArray[rowIndex] + imageWidth + gapWidth > pageWidth) {
				
				// Adding that image to the row would overflow off the page, therefore our current row is complete.
				
				/**/debugMsg(debugUL, 'li', `Row <strong>${rowIndex}</strong>: ${rowArray[rowIndex].length} images, width: ${rowWidthArray[rowIndex]}`);
				
				// To make this row of images expand to fill the page width, we will increase their size based on a new common height.
				// We will calculate that height by getting the ratio of the page width to the row width.  However, the gaps between
				// images are going to remain static.  So before calculating, we remove all gaps from the respective widths.
				const numGaps = rowArray[rowIndex].length;					// There is one less gap then there are images, but there's half
																			// a gap at both the beginning and the end of the entire row
				const ratio = (pageWidth - numGaps * gapWidth) / (rowWidthArray[rowIndex] - numGaps * gapWidth);
//				const ratio = pageWidth / rowWidthArray[rowIndex];
				const newHeight = Math.floor(originalHeight * ratio);		// Round down to an integer value

				/**/debugMsg(debugUL, 'li', `rowWidth: ${rowWidthArray[rowIndex]} | numGaps: ${numGaps} = ${numGaps * gapWidth}px | ratio: ${pageWidth - numGaps * gapWidth} / ${rowWidthArray[rowIndex] - numGaps * gapWidth} = ${ratio} | new width: ${(rowWidthArray[rowIndex] - numGaps * gapWidth) * ratio + numGaps * gapWidth} | new height: ${newHeight}`);
				/**/if (ratio > br_maxRatio) debugMsg(debugUL, 'li', `<p> - Greater than maxHeight of ${maxHeight}!</p>`);

				// Check if the height is greater than the maximum ratio would allow.  If so, the images in this row will be given a new width
				// based on the ratio, but height will be capped.  Then CSS styling must be used to crop the image (eg. object-fit: cover)
				
				/**/var debugTotalWidth = 0;
				/**/var debugOL = debugMsg(debugUL, 'ol');
				
				if (ratio <= br_maxRatio) {
					
					// Apply the new height to each image in this row, leaving width: auto
					for (const el of rowArray[rowIndex]) {
						const img = el.getElementsByTagName('img')[0];
						
						if (img.width < br_minWidth) {
							
							// This thumbnail is narrower than the minimum width, so we apply the minimum width times the ratio
							img.style.width = (br_minWidth * ratio) + "px";
							img.style.height = newHeight + "px";
							
							// Since this overrules the image's natural aspect ratio, it should be set to crop
							img.classList.add("cropped");
							
							/**/debugMsg(debugOL, 'li', `Image: Cropped! New image height: ${newHeight} - Resulting width x height: ${img.getBoundingClientRect().width} x ${img.getBoundingClientRect().height}`);
							
						} else {
						
							img.style.width = "auto";
							img.style.height = newHeight + "px";
							
							// If this image had the "cropped" class, it must be removed
							img.classList.remove("cropped");
							
							/**/debugMsg(debugOL, 'li', `Image: New image height: ${newHeight} - Resulting width x height: ${img.getBoundingClientRect().width} x ${img.getBoundingClientRect().height}`);
						}

						/**/debugTotalWidth += img.getBoundingClientRect().width
					}

				} else {

					for (const el of rowArray[rowIndex]) {
						const img = el.getElementsByTagName('img')[0];
						
						const oldWidth = Number(img.width);		// Get original image dimensions
						const oldHeight = Number(img.height);
						const newWidth = Math.floor(newHeight * (oldWidth / oldHeight) * 10) / 10;		// Determine new width based on original
																										// aspect ratio, to 1 decimal point
						img.style.width = newWidth + "px";		// Assign the new width
						img.style.height = maxHeight + "px";	// Limit the height as per br_maxRatio

						/**/debugMsg(debugOL, 'li', `Image: Cropped! New image width: ${img.width} - Resulting width x height: ${img.getBoundingClientRect().width} x ${img.getBoundingClientRect().height}`);

						// Add a class for ease of CSS styling
						img.classList.add("cropped");
					
						/**/debugTotalWidth += img.getBoundingClientRect().width
					}

				}
				
				/**/debugMsg(debugUL, 'li', `Resulting width of row ${rowIndex}: ${debugTotalWidth + numGaps * gapWidth}`);
				
				// Having finished resizing of this row, we move on to start the next row
				rowIndex++;
				rowArray[rowIndex] = [];
				rowWidthArray[rowIndex] = 0;
				
				
			} else if (i == br_imageArray.length - 1) {
				// In this case, we have reached the end of the image thumbnails, but have not capped off an entire row.
				// This last row may have just one or two thumbnails in it, and growing them to fill the page width could result
				// in very lopsided aspect ratios.  So we won't grow the row's height, But we still need to apply the minimum
				// width to each thumbnail.
				
				for (const el of rowArray[rowIndex]) {
					const img = el.getElementsByTagName('img')[0];
					
					if (img.width < br_minWidth) {
						
						img.style.width = br_minWidth + "px";
						img.style.height = img.height + "px";	// Explicitly keep the current height
						
						// Since this overrules the image's natural aspect ratio, it should be set to crop
						img.classList.add("cropped");
						
					}
				}
				
				// Since we reached the final thumbnail, we should end the entire process
				break;
			}
			
			// Add this image to the current row
			rowArray[rowIndex].push(br_imageArray[i]);
			
			// Add this image's width (and margins) to the current row's total width
			rowWidthArray[rowIndex] += imageWidth + gapWidth;

		}
		
	// The thumbnails div may have been hidden so that these size changes wouldn't make the page layout jump around, and now that resizing is complete, it should be made visible.
	document.getElementById('thumbnails').style.visibility = 'visible';
	}
}


if (br_imageArray.length > 0) {
	
	// If the page hasn't finished loading yet, add an event listener.  If it has finished loading, just run the function immediately.
	if (document.readyState === "loading") {
		window.addEventListener("load", br_adjustThumbnailSizes);
	} else {
		br_adjustThumbnailSizes();
	}

	window.addEventListener("resize", br_adjustThumbnailSizes);

}

