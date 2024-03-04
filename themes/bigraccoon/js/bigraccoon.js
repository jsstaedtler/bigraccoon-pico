// Define global variables to store various element positions
var navBar;
var navBarTop;
var logo;
var logoMid;
var homeButton;
var backtotopButton;
var bgs;
var bgWidth;
var bgRightOffset;


function toggleFont() {
	if (document.getElementById("fonttoggle").checked) {
		document.body.classList.add("sans");
	} else {
		document.body.classList.remove("sans");
	}
}

// Handlers for opening and closing the navPanel menu (when the screen is too narrow for the full navBar)
function openMenu() {
	document.getElementById("navLinks").classList.add("panelVisible");
	document.getElementById("navBuffer").classList.add("panelVisible");
	document.getElementById("navPanelToggle").classList.add("close");
}

function closeMenu() {
	document.getElementById("navLinks").classList.remove("panelVisible");
	document.getElementById("navBuffer").classList.remove("panelVisible");
	document.getElementById("navPanelToggle").classList.remove("close");
}

function toggleMenu() {
	if (document.getElementById("navLinks").matches(".panelVisible")) {
		closeMenu();
	} else {
		openMenu();
	}
}

// On click, test whether the navPanel menu is visible, and if the click was outside of it, close the navPanel
function closeMenuOnClickOutside() {
	if (document.getElementById("navLinks").matches(".panelVisible") && !document.getElementById("nav").contains(event.target)) {
		closeMenu();
	}
}

document.addEventListener("click", closeMenuOnClickOutside);


function resetDimensions() {
	// We don't measure the top of #nav, because it may have been changed to 0.
	// Instead, we get the top position of the #main element (adjusted by how far the page has scrolled), and subtract the height of the navbar.
	// That vertical position on the page is the threshold where the navbar should stick and unstick.
	navBar = document.getElementById("nav");
	navBarTop = document.getElementById("main").getBoundingClientRect().top + window.scrollY - navBar.clientHeight;
	
	// Get the vertical position of the middle of the main logo (by getting its current position on-screen and adjusting for how far it's scrolled)
	logo = document.getElementById("headerlogo");
	logoMid = logo.getBoundingClientRect().top + window.scrollY + (logo.clientHeight / 2);
	
	homeButton = document.getElementById("homebutton");
	backtotopButton = document.getElementById("backtotopbutton");
	
	// The page background should be 100% wide and centered, but if a scrollbar has shrunken the viewport, the page BG remains in place but everything else gets a new centerline a bit to the left.
	// The Body object of the page has a clientWidth that measures the true width of the viewport (without the scrollbar).
	bgs = document.getElementsByClassName("bg fixed");
	bgWidth = bgs[0].clientWidth;
	bgWidthOffset = bgWidth - document.body.clientWidth;
	
	// To precisely overlap the navbar background with the page background, we shift its centerline back to the right, by the same amount that the scrollbar shifted it left.
	navBar.style.backgroundSize = `auto, auto, ${bgWidth}px`;
	navBar.style.backgroundPosition = `center, center, calc(50% + ${bgWidthOffset/2}px) 0`;
	
	closeMenu();
	scrollChanges();
}

function scrollChanges() {
	// If the navBar somehow scrolled up above the screen without sticking, fix the value of navBarTop
/*	if (window.pageYOffset >= navBar.offsetTop) {
		navBarTop = document.getElementById("main").getBoundingClientRect().top + window.scrollY - navBar.clientHeight;
	} */
	
	// When the page has scrolled further than the expected position of the navbar, we change the navbar to sticky.
	// CSS will take care of positioning it on the page.
	if (window.pageYOffset >= navBarTop) {
		navBar.classList.add("sticky");
	} else {
		navBar.classList.remove("sticky");
	}
	
	// If the page scrolls past the middle of the logo, present the logo on the navbar
	if (window.pageYOffset >= logoMid) {
		homeButton.classList.remove("hidden");
		backtotopButton.classList.remove("hidden");
	} else {
		homeButton.classList.add("hidden");
		backtotopButton.classList.add("hidden");
	}
}

// Once the page has loaded to this point in the script, immediately initialize the global variables with position values, then test the scroll position.
resetDimensions();

// When the window scrolls, test whether it's past the top of the navbar.
//window.onscroll = function() { stickNavBar() };
window.addEventListener("scroll", scrollChanges);

// When the window is resized/zoomed, reset the position variables, and test the scroll position again.
window.addEventListener("resize", resetDimensions);
window.addEventListener("zoom", resetDimensions);


// At this point, we can assume the page has been loaded (despite media still possibly downloading, which we don't want to wait for).  We can remove the "is-preload" class from body, so initial animations can start.

document.body.classList.remove("is-preload");
