// Global variables
var imageList;
var imgblockMembers;
var imgblockIndex;


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


// Open the lightbox modal and set the image within it.
// Also, collect all other members within the same imgblock, and get the index of the one specified, to permit switching to the previous or next image.
function lightboxOpen(i) {
	document.getElementById("lightbox-image").src = imageList[i].src;			// Set the big image to what was clicked
	imgblockMembers = getAncestorByClass(imageList[i], "imgblock").children;	// Note: this returns the <p> elements containing the images,
																				//   so use "imgblockMembers[i].firstElementChild" to get each image
	if (imgblockMembers) {
		imgblockIndex = 0;
		while (imgblockMembers[imgblockIndex].firstElementChild && imgblockMembers[imgblockIndex].firstElementChild !== imageList[i]) {
			imgblockIndex++;
		}
	} else {
		imgblockIndex = null;	// With this value, you can't switch to the next/previous image
	}
	
	lightboxChange(0);
	document.getElementById("lightbox").style.display = "block";
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
	if (imgblockMembers[imgblockIndex + n]) {
		imgblockIndex += n;
		document.getElementById("lightbox-image").src = imgblockMembers[imgblockIndex].firstElementChild.src;
	}
	
	// Determine whether next/prev buttons should be enabled
	if (imgblockMembers[imgblockIndex + 1]) {
		document.getElementById("lightbox-next").classList.remove("disabled");
	} else {
		document.getElementById("lightbox-next").classList.add("disabled");		
	}
	if (imgblockMembers[imgblockIndex - 1]) {
		document.getElementById("lightbox-prev").classList.remove("disabled");
	} else {
		document.getElementById("lightbox-prev").classList.add("disabled");		
	}
}

function lightboxClose() {
	document.getElementById("lightbox").style.display = "none";
}

// Get all images inside an imageblock, and make them clickable to open in the lightbox
imageList = document.querySelectorAll("div.imgblock img");
for (var i = 0; i < imageList.length; i++) {
	imageList[i].setAttribute("onclick", "lightboxOpen(" + i + ");");
	imageList[i].style.cursor = "zoom-in";
} 

