// Global variables
const br_lightboxStateOpened = "lightbox-open";		// Any arbitrary string to identify the state of the lightbox being open

var br_imageList;
var br_imgblockMembers;
var br_imgblockIndex;
var br_imageWidth;
var br_imageHeight;

var br_canvas = document.getElementById("canvas");
var br_ctx = br_canvas.getContext('2d');

var cameraOffset;
var cameraZoom = 1;
var lastZoom = 1;
var initialPinchDistance = null;

var MAX_ZOOM = 5;
var MIN_ZOOM = 1;
var SCROLL_SENSITIVITY = 0.001


// Find first ancestor of an element e with tag name t, or null if not found
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


// Open the lightbox modal and set the image within it.  Also prevent the background page from scrolling.
// Finally, collect all other members within the same imgblock, and get the index of the one specified, to permit switching to the previous or next image.
function lightboxOpen(i) {
	document.getElementById("lightbox-image").src = br_imageList[i].src;			// Set the big image to what was clicked
	br_imgblockMembers = getAncestorByClass(br_imageList[i], "imgblock").children;	// Note: this returns the <p> elements containing the images,
																					//   so use "br_imgblockMembers[i].firstElementChild" to get each image

	// Find the index of the selected image within its imgblock
	if (br_imgblockMembers) {
		br_imgblockIndex = 0;
		while (br_imgblockMembers[br_imgblockIndex].firstElementChild && br_imgblockMembers[br_imgblockIndex].firstElementChild !== br_imageList[i]) {
			br_imgblockIndex++;
		}
	} else {
		br_imgblockIndex = null;	// Given this value, you can't switch to the next/previous image
	}
	
	// Load the current image (0 means not decrementing nor incrementing the br_imgblockIndex)
	lightboxChange(0);
	
	// Display the lightbox modal, disable page scrolling
	document.getElementById("lightbox").style.display = "block";
	document.getElementsByTagName('html')[0].classList.add("noscroll");
	
	draw();
}


function lightboxZoomIn() {
	i = document.getElementById("lightbox-image");
	i.style.maxWidth = null;
	i.style.maxHeight = null;
	i.style.cursor = "zoom-out";
	i.setAttribute("onclick", "lightboxZoomOut();");
}


function lightboxZoomOut() {
	i = document.getElementById("lightbox-image");
	i.style.maxWidth = "100%";
	i.style.maxHeight = "100%";
	i.style.cursor = "zoom-in";
	i.setAttribute("onclick", "lightboxZoomIn();");
}


function lightboxChange(n) {	// n=1 for next image, n=-1 for previous image, 0 for no change (but reevaluate what buttons ae enabled)
	// Be sure the next/previous index actually exists before switching to it
	if (br_imgblockMembers[br_imgblockIndex + n]) {
		br_imgblockIndex += n;
		document.getElementById("lightbox-image").src = br_imgblockMembers[br_imgblockIndex].firstElementChild.src;
	}
	
	// Determine whether next/prev buttons should be enabled
	if (br_imgblockMembers[br_imgblockIndex + 1]) {
		document.getElementById("lightbox-next").classList.remove("disabled");
	} else {
		document.getElementById("lightbox-next").classList.add("disabled");		
	}
	if (br_imgblockMembers[br_imgblockIndex - 1]) {
		document.getElementById("lightbox-prev").classList.remove("disabled");
	} else {
		document.getElementById("lightbox-prev").classList.add("disabled");		
	}
	
	// Get size of the br_canvas and the image, to determine the minimum zoom level
	// (it never needs to be so small that both image dimensions are less than 100% of the br_canvas)
	cw = window.innerWidth;
	ch = window.innerHeight;
	br_imageWidth = document.getElementById("lightbox-image").naturalWidth;
	br_imageHeight = document.getElementById("lightbox-image").naturalHeight;
	
	// Get scale of image width and height compared to the br_canvas, and select the smallest one as our new minimum
	scaleWidth = cw / br_imageWidth;
	scaleHeight = ch / br_imageHeight;
	MIN_ZOOM = Math.min(scaleWidth, scaleHeight);
	MAX_ZOOM = MIN_ZOOM * 5;
	
	// Reset the position and zoom level of the br_canvas, so the image fits in the centre
	cameraOffset = {x: window.innerWidth / 2, y: window.innerHeight / 2};
	cameraZoom = MIN_ZOOM;
/*
	console.log(`cw: ${cw}`); //300 - 
	console.log(`ch: ${ch}`); //200 - 
	console.log(`iw: ${br_imageWidth}`); //300 - 100 - 150 (0 to 0)
	console.log(`ih: ${br_imageHeight}`); //600 - 200 - 300 (-50 to +50)
	console.log(`MIN_ZOOM: ${MIN_ZOOM}`); //1 - 0.333 - 0.5
*/
}


function lightboxClose() {
	document.getElementById("lightbox").style.display = "none";
	document.getElementsByTagName('html')[0].classList.remove("noscroll");
}


function lightboxExitButton() {
	history.back();
}


function onPopstate(e) {
	if (e.state) {
		console.log(e.state);
		
		if (e.state.lightboxIsOpen) {
			lightboxOpen(e.state.lightboxImage);
		} else {
			lightboxClose();
		}
	}
}


function onImageClick(i) {
	// Add a browser history entry, so that clicking the Back button won't exit this entire page.  This has to be done on a link-click event.
	// If the user goes Back but then Forward again, we need not push this new state - the state will be restored automatically.
	console.log("Pushing state - lightboxOpen: true");
	history.pushState({lightboxIsOpen: true, lightboxImage: i}, "");
	
	// Now open the lightbox for the clicked image
	lightboxOpen(i);
}


// Canvas stuff


function draw() {
	
    br_canvas.width = window.innerWidth;
    br_canvas.height = window.innerHeight;
	
    // Translate to the br_canvas centre before zooming - so you'll always zoom on what you're looking directly at
    br_ctx.translate(window.innerWidth / 2, window.innerHeight / 2);
    br_ctx.scale(cameraZoom, cameraZoom);
    br_ctx.translate(-window.innerWidth / 2 + cameraOffset.x, -window.innerHeight / 2 + cameraOffset.y);
    br_ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
	
	// Get the image, and its width and height
	i = document.getElementById("lightbox-image");
	iw = i.naturalWidth;
	ih = i.naturalHeight;

	// Centre the image on the br_canvas (where 0,0 is the middle of the br_canvas)
	br_ctx.drawImage(i, -iw / 2, -ih / 2);
	
    requestAnimationFrame(draw);
}


// Gets the relevant location from a mouse or single touch event
function getEventLocation(e) {
    if (e.touches && e.touches.length == 1) {
        return {x: e.touches[0].clientX, y: e.touches[0].clientY};
    }
    else if (e.clientX && e.clientY) {
        return {x: e.clientX, y: e.clientY};     
    }
}


let isDragging = false;
let dragStart = {x: 0, y: 0};


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
	
		// The image may only pan if it is wide/tall enough to exceed the canvas
		// Calculate how far the image may move
		pannableWidth = Math.max(0, (br_imageWidth - window.innerWidth / cameraZoom) / 2);
		pannableHeight = Math.max(0, (br_imageHeight - window.innerHeight / cameraZoom) / 2);

		// Put the cameraOffset back if it exceeds that limit
		cameraOffset.x = Math.max(cameraOffset.x, window.innerWidth / 2 - pannableWidth);
		cameraOffset.x = Math.min(cameraOffset.x, window.innerWidth / 2 + pannableWidth);
		cameraOffset.y = Math.max(cameraOffset.y, window.innerHeight / 2 - pannableHeight);
		cameraOffset.y = Math.min(cameraOffset.y, window.innerHeight / 2 + pannableHeight);
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
		
		// The image may only pan if it is wide/tall enough to exceed the canvas
		// Calculate how far the image may move
		pannableWidth = Math.max(0, (br_imageWidth - window.innerWidth / cameraZoom) / 2);
		pannableHeight = Math.max(0, (br_imageHeight - window.innerHeight / cameraZoom) / 2);

		// Put the cameraOffset back if it exceeds that limit
		cameraOffset.x = Math.max(cameraOffset.x, window.innerWidth / 2 - pannableWidth);
		cameraOffset.x = Math.min(cameraOffset.x, window.innerWidth / 2 + pannableWidth);
		cameraOffset.y = Math.max(cameraOffset.y, window.innerHeight / 2 - pannableHeight);
		cameraOffset.y = Math.min(cameraOffset.y, window.innerHeight / 2 + pannableHeight);

        
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

// When the browser Back button is pressed
window.addEventListener("popstate", (e) => onPopstate(e));

// Establish a known history state, during this initial page load, in which the lightbox is closed
console.log("Replacing state - lightboxOpen: false");
history.replaceState({lightboxIsOpen: false, lightboxImage: null}, "");

// Get all images inside an imageblock, and make them clickable to open in the lightbox
br_imageList = document.querySelectorAll("div.imgblock img");
for (var i = 0; i < br_imageList.length; i++) {
	br_imageList[i].setAttribute("onclick", "onImageClick(" + i + ");");
	br_imageList[i].style.cursor = "zoom-in";
} 

