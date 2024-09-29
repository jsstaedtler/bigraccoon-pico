<?php

class PicoPageMIMEType extends AbstractPicoPlugin
{
    const API_VERSION = 3;
    protected $dependsOn = array();
    protected $enabled = false;			// Default plugin state, until specified otherwise in the site config
    

    public function onConfigLoaded(array &$config)
    {
		// Check whether our plugin is enabled in the global site config
        if (isset($config['PicoPageMIMEType']['enabled']) && $config['PicoPageMIMEType']['enabled'] == 'true')
            $this->enabled = true;
    }
	
	
    public function onCurrentPageDiscovered(
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
		if ($currentPage !== null) {
			if (isset($currentPage['meta']['mimetype'])) {		// Check if there is a "mimetype" value in this page's frontmatter
				header_remove('Content-Type');					// Replace whatever MIME type this page was going to have
				header('Content-Type: ' . $currentPage['meta']['mimetype']);
			}
		}	
    }

}

?>
