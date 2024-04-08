// ********************
// * Global variables *
// ********************

let br_lightboxImage;									// Object representing the one big image to be featured in the lightbox
let br_canvas = document.getElementById("canvas");		// Object representing the HTML canvas element
	br_canvas.width = window.innerWidth;					// Immediately set its width and height to fill the entire page
	br_canvas.height = window.innerHeight;
let br_ctx = br_canvas.getContext('2d');				// The 2D context object for the canvas
let canUpdateCanvas = false;							// Controls whether canvas updates should be taking place

let cameraOffset = {x: 0, y: 0};	// Coordinates for centering the image within the canvas
let cameraZoom = 1;					// Current zoom level - the ratio of image scale to canvas (higher value means bigger image, must be > 0)
let lastZoom = 1;					// Previous zoom level for some calculations
let initialPinchDistance = null;	// For calculations related to pinch-to-zoom
let isDragging = false;
let dragStart = {x: 0, y: 0};

let MAX_ZOOM = 5;					// Maximum and minimum permitted zoom levels (which can change based on image and canvas sizes)
let MIN_ZOOM = 1;						// (Minimum must be > 0)
const SCROLL_SENSITIVITY = 0.001	// Specifically for how much a scroll wheel changes the zoom level (higher value is faster)



// *****************************
// * Helper functions, for     *
// * finding certain DOM nodes *
// *****************************

// Find first ancestor of an element e that has class name c, or null if not found
function getAncestorByClass(e, c) {
	c = c.toLowerCase();

	while (e && e.parentNode) {
		e = e.parentNode;
		if (e.className && e.classList.contains(c)) {
			return e;
		}
	}

	return null;
}


// Find all descendants of an element el that have tag name tag, or null if none found
function getDescendantsByTagName(el, tag) {
	tag = tag.toLowerCase();
	
	let result = [];
	
	for (const child of el.children) {
		if (child.tagName.toLowerCase() == tag) {
			result.push(child);
		} else {
			nextChildren = getDescendantsByTagName(child, tag)
			result.push(...nextChildren);
		}
	}

	if (result.length == 0) {
		return null;
	} else {
		return result;
	}
}



// ********************
// * Lightbox actions *
// ********************

// The image may only pan if it is wide/tall enough to exceed the canvas, and then only as far as edge of the image.
// Whenever the image or canvas size changes, we must call this function to move the image if necessary to fit it within the canvas.
function fitImageToCanvas() {
	
		// Ensure currentZoom is not less than MIN_ZOOM
		cameraZoom = Math.max(cameraZoom, MIN_ZOOM);

		// Calculate how far the image may move based on the current zoom level.
		let pannableWidth = Math.max(0, (br_lightboxImage.naturalWidth - window.innerWidth / cameraZoom) / 2);
		let pannableHeight = Math.max(0, (br_lightboxImage.naturalHeight - window.innerHeight / cameraZoom) / 2);

		// Put the cameraOffset back if it exceeds that limit
		cameraOffset.x = Math.max(cameraOffset.x, window.innerWidth / 2 - pannableWidth);
		cameraOffset.x = Math.min(cameraOffset.x, window.innerWidth / 2 + pannableWidth);
		cameraOffset.y = Math.max(cameraOffset.y, window.innerHeight / 2 - pannableHeight);
		cameraOffset.y = Math.min(cameraOffset.y, window.innerHeight / 2 + pannableHeight);
		
}


// This function draws the image to the canvas context.  It uses global variables cameraZoom and cameraOffset to scale and
// position the context.  It will then request to run again on the next canvas refresh.  This way, it will run nearly as
// frequently as every screen refresh, providing live updates as the user pans and zooms the image.

// A note about the canvas: translate and scale operations act on *future* drawings, not whatever has already been drawn.
// Setting the width or height properties of the canvas object will erase everything, and reset translation and scale.
function drawCanvas() {
	
	// Proceed only if we know how the image should be positioned (in case somehow we got here before that was defined)
	if (cameraOffset && cameraZoom) {
	
		// Update the size of the canvas to fill the window, in case it has resized.
		// At the same time, this will erase and reset the canvas scale and coordinates.
		br_canvas.width = window.innerWidth;
		br_canvas.height = window.innerHeight;
		
		// Apply the current zoom level and context position.  But before doing so, move the centre of the context to the centre of the screen.
		// This way, the zoom effect won't appear to come from some random point of the screen.
		br_ctx.translate(window.innerWidth / 2, window.innerHeight / 2);
		br_ctx.scale(cameraZoom, cameraZoom);
		br_ctx.translate(-window.innerWidth / 2 + cameraOffset.x, -window.innerHeight / 2 + cameraOffset.y);
		
		// Clear existing imagery (so bits of the previous frame don't stick out from under the new one)
		//br_ctx.clearRect(-window.innerWidth / 2, -window.innerHeight / 2, window.innerWidth / 2, window.innerHeight / 2);

		// Draw the image, centred on the canvas (where 0,0 is the middle of the screen)
		br_ctx.drawImage(br_lightboxImage, -br_lightboxImage.naturalWidth / 2, -br_lightboxImage.naturalHeight / 2);
		
		// If the glabal variable canUpdateCanvas is true, register this drawCanvas() function to run on next canvas refresh
		if (canUpdateCanvas) {
			requestAnimationFrame(drawCanvas);
		}
	
	} else {
		console.error(`drawCanvas: Undefined value! cameraOffset = ${cameraOffset} - cameraZoom = ${cameraZoom}`);
	}
}


// Open the lightbox modal, and prevent the background page from scrolling.  Then load the image.
function lightboxOpen() {

	// If somehow no br_lightboxImage has been defined, don't even try to continue
	if (br_lightboxImage) {
		
		// Display the lightbox modal, then disable page scrolling
		document.getElementById("lightbox").classList.remove("disabled");		// Remove the CSS that hides the lightbox element
		document.getElementsByTagName('html')[0].classList.add("noscroll");		// In CSS, html.noscroll has "overflow: hidden" to get rid of scrollbars
		
		// Kick off the canvas object with the current br_lightboxImage (passing value 0 so it neither decrements nor increments the current image,
		// and true to reset any existing zoom and offset)
		lightboxChange(0, true);
		
	} else {
		console.error("lightboxOpen: br_lightboxImage undefined!");
	}
	
}


// Based on the current image being displayed, we must produce prev/next buttons to switch to other members of its imgblock.
// That determination will be made every time the image changes.
//
// currentImage: An img element object
// change (integer): 1 for next image, -1 for previous image, 0 for no change (but reevaluate what buttons are enabled)
// reset (boolean): true to clear and recalculate cameraOffset/cameraZoom, false to keep them unchanged
function lightboxChange(change, reset) {
		
	// Only proceed if the lightbox is being displayed, and br_lightboxImage has been defined
	if (!canvas.classList.contains("disabled") && br_lightboxImage) {
			
		// Stop canvas updates, which may already be taking place using the current image
		canUpdateCanvas = false;
	
		// Get the "imgblock" div that the current image belongs to (if any).
		// Then get all of the <img> elements within it (which are likely nested within <p> and <a> elements)
		const imgblock = getAncestorByClass(br_lightboxImage, "imgblock");
		const imgblockMembers = getDescendantsByTagName(imgblock, "img");
		
		// If a parent imgblock was found, find the index of the current image within it
		let currentIndex = 0;
		if (imgblockMembers) {
			
			// Starting at index 0, increment until we find the current image
			while (imgblockMembers[currentIndex] && imgblockMembers[currentIndex] !== br_lightboxImage) {
				currentIndex++;
			}
			
		} else {
			currentIndex = null;	// This indicates there are no images before or after the current one to switch to
		}
		
		// Now we're going to get the image specified by the "change" value
		let newIndex = currentIndex + change;
		
		// Be sure the desired index actually exists before switching our image to it
		if (imgblockMembers[newIndex]) {
			br_lightboxImage = imgblockMembers[newIndex];
		}
		
		// Determine whether next/prev buttons should be enabled
		if (imgblockMembers[newIndex + 1]) {
			document.getElementById("lightbox-next").classList.remove("disabled");
		} else {
			document.getElementById("lightbox-next").classList.add("disabled");		
		}
		if (imgblockMembers[newIndex - 1]) {
			document.getElementById("lightbox-prev").classList.remove("disabled");
		} else {
			document.getElementById("lightbox-prev").classList.add("disabled");		
		}
		
		// Get size of the canvas and of br_lightboxImage, to determine the minimum zoom level
		// (it never needs to be so small that both image dimensions are less than 100% of the br_canvas)
		cw = window.innerWidth;
		ch = window.innerHeight;
		iw = br_lightboxImage.naturalWidth;
		ih = br_lightboxImage.naturalHeight;
		
		// Get scale of image width and height compared to the canvas width and height, and select the smallest of the two as our new minimum
		scaleWidth = cw / iw;
		scaleHeight = ch / ih;
		MIN_ZOOM = Math.min(scaleWidth, scaleHeight);
		MAX_ZOOM = MIN_ZOOM * 5;
			
		if (reset) {
			
			// Reset the position and zoom level of the canvas, so the image fits precisely in the centre
			cameraOffset = {x: window.innerWidth / 2, y: window.innerHeight / 2};
			cameraZoom = MIN_ZOOM;

		} else {
			
			// We don't want to reset the existing values, but they must be adjusted for the new image
			fitImageToCanvas();
		}
		
		// Resume canvas updates using the new image
		canUpdateCanvas = true;
		drawCanvas();
		
	}
	
}


// This performs the actual removal of the lightbox from the page display
function lightboxClose() {
	
	// Stop refreshing the canvas
	canUpdateCanvas = false;
	console.log("canUpdateCanvas = false");
	
	// Hide the lightbox <div>, and reenable scrolling on the page
	document.getElementById("lightbox").classList.add("disabled");
	document.getElementsByTagName('html')[0].classList.remove("noscroll");

}


// Clicking the "close" button in the lightbox is equivalent to clicking the browser's Back button.
// That fires a "popstate" event, so that event handler, onPopstate(), will execute the closing of the lightbox.
function lightboxExitButton() {
	history.back();
}



// ***************************
// * Event handler functions *
// ***************************


// Gets the coordinate location of a mouse or single (one-finger) touch event
function getEventLocation(e) {
    if (e.touches && e.touches.length == 1) {
        return {x: e.touches[0].clientX, y: e.touches[0].clientY};
    }
    else if (e.clientX && e.clientY) {
        return {x: e.clientX, y: e.clientY};     
    }
}


function onImageClick(e) {
	
	// Store the <img> object that was clicked.  Note that when you mouse-click or touch-tap an <a> element, the
	// resulting event's target is actually the child element contained within the <a> (in this case, the <img>).
	// But if you tab-select to the <a> element and press Enter, the event's target is the <a> element itself, in
	// which case we must capture the target's child element.
	if (e.target.tagName == "A") {
		br_lightboxImage = e.target.firstElementChild;
	} else {
		br_lightboxImage = e.target;
	}
	
	// Add a browser history entry, so that clicking the Back button won't exit this entire page.  This only needs to be done on a
	// link-click event.  That's because, if the user goes Back but then Forward again, the state will be restored automatically.
	// Note that the state must be a "serializable" object, which an <img> element is not.  Simply passing the "br_lightboxImage"
	// object will fail.  So we need a complicated workaround: get an array of all <img> nodes in the page (that reside in imgblocks),
	// find the index of br_lightboxImage within that, and pass the index value as the history state.
	
	// Note that querySelectorAll() returns a nodeList, and we must convert it to an array
	const images = Array.from(document.querySelectorAll("div.imgblock img"));
	const index = images.findIndex((image) => image == br_lightboxImage);
	
	const state = {lightboxIsOpen: true, imageIndex: index};
	history.pushState(state, "");		// This method needs an empty 2nd argument for historical reasons
	
	// Now open the lightbox
	lightboxOpen();
}


// Occurs when the history state changes - ie. the Back or Forward browser button was pressed
function onPopstate(e) {
	
	// Event e should contain the history state information which needs to be restored (refer to onImageClick())
	if (e.state.lightboxIsOpen) {
		
		// In this state, the lightbox was opened.  The state info contains an index to the image that needs to be loaded.
		// First get an array of all <img> nodes in the page (that reside in imgblocks), then open the one with the specified index.
		const images = document.querySelectorAll("div.imgblock img");
		br_lightboxImage = images[e.state.imageIndex];
		lightboxOpen();
		
	} else {
		
		// In this state, the lightbox was closed
		lightboxClose();
		
	}

}


function onPointerDown(e) {
    isDragging = true;
    dragStart.x = getEventLocation(e).x / cameraZoom - cameraOffset.x;
    dragStart.y = getEventLocation(e).y / cameraZoom - cameraOffset.y;
	
	//console.log("Pointer down");
}


function onPointerUp(e) {
    isDragging = false;
    initialPinchDistance = null;
    lastZoom = cameraZoom;
	
	//console.log(`cameraOffset: ${cameraOffset.x}, ${cameraOffset.y}`);	
	//console.log("Pointer up");
}


function onPointerMove(e) {
    if (isDragging) {
        cameraOffset.x = getEventLocation(e).x / cameraZoom - dragStart.x;
        cameraOffset.y = getEventLocation(e).y / cameraZoom - dragStart.y;
	
		// Fix image position if it no longer fits properly in the canvas
		fitImageToCanvas();
	}
}


function onDblclick(e) {
	if (cameraZoom == MIN_ZOOM) {
		// If the image is at the minimum zoom level (ie. immediately after it's been open), then increase it
		adjustZoom(1);
	} else {
		// Otherwise, zoom out to the minimum level (by trying to set it to an extremely small value below MIN_ZOOM)
		adjustZoom(null, 0.0001);
	}
	
	onPointerUp(e);
}


function handleTouch(e, singleTouchHandler) {
	//console.log(`handleTouch: ${e.touches.length} touches`)
	
    if (e.touches.length == 0) {
        onPointerUp(e);
    }
    else if (e.touches.length == 1) {
        singleTouchHandler(e);
    }
    else if (e.type == "touchmove" && e.touches.length == 2) {
        isDragging = false;
        handlePinch(e);
    }
}


function handlePinch(e) {
    e.preventDefault();
    
    let touch1 = {x: e.touches[0].clientX, y: e.touches[0].clientY};
    let touch2 = {x: e.touches[1].clientX, y: e.touches[1].clientY};
    
    // This is distance squared, but no need for an expensive sqrt as it's only used in ratio
    let currentDistance = (touch1.x - touch2.x)**2 + (touch1.y - touch2.y)**2;
    
    if (initialPinchDistance == null) {
        initialPinchDistance = currentDistance;
    } else {
        adjustZoom(null, currentDistance/initialPinchDistance);
    }
}


function adjustZoom(zoomAmount, zoomFactor) {
    if (!isDragging) {
        if (zoomAmount) {
            cameraZoom += zoomAmount;
        } else if (zoomFactor) {
            //console.log(zoomFactor);
            cameraZoom = zoomFactor * lastZoom;
        }
        
        cameraZoom = Math.min(cameraZoom, MAX_ZOOM);
        cameraZoom = Math.max(cameraZoom, MIN_ZOOM);
		
		// Fix image position if it no longer fits properly in the canvas
		fitImageToCanvas();
        
        //console.log(zoomAmount);
		//console.log(cameraOffset);
    }
}



// Canvas event handlers
br_canvas.addEventListener('mousedown', onPointerDown);
br_canvas.addEventListener('touchstart', (e) => handleTouch(e, onPointerDown));
br_canvas.addEventListener('mouseup', onPointerUp);
br_canvas.addEventListener('mouseleave', onPointerUp);
br_canvas.addEventListener('touchend', (e) => handleTouch(e, onPointerUp));
br_canvas.addEventListener('touchcancel', (e) => handleTouch(e, onPointerUp));
br_canvas.addEventListener('mousemove', onPointerMove);
br_canvas.addEventListener('touchmove', (e) => handleTouch(e, onPointerMove));
br_canvas.addEventListener('wheel', (e) => adjustZoom(e.deltaY * SCROLL_SENSITIVITY));
br_canvas.addEventListener('dblclick', onDblclick);

// When the browser Back/Forward button is pressed
window.addEventListener("popstate", onPopstate);

// If the browser gets resized, recalculate positioning of the image
window.addEventListener("resize", (e) => lightboxChange(0, false));


// Establish a known history state, during this initial page load, in which the lightbox is closed
//console.log("Replacing state - lightboxOpen: false");
history.replaceState({lightboxIsOpen: false, lightboxImage: null}, "");


// Now we must make all images on this page clickable.  (Or more specifically, all imgblock images)
// The cleanest way is to wrap each one in an <a> tag.  This will improve accessibility by allowing tabbing through the images,
// and it will make browser history traversal work as expected.

// Act on all images residing within imgblocks
for (let image of document.querySelectorAll("div.imgblock img")) {
	
	// Find the parent node of our image
	let parent = image.parentNode;
	
	// Create a new <a> element with the necessary attributes
	let newLink = document.createElement("a");
	newLink.setAttribute("href", "javascript:void(0);");
	newLink.addEventListener("click", onImageClick);
	newLink.style.cursor = "zoom-in";
	
	// Insert the new <a> element as a child of the parent node
	parent.appendChild(newLink);
	
	// Now move the image from its current parent to the new <a> element
	newLink.appendChild(image);
} 

