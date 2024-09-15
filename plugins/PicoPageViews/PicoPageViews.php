<?php

use Symfony\Component\Yaml\Yaml;

class PicoPageViews extends AbstractPicoPlugin
{
    const API_VERSION = 3;
    protected $enabled = true;
    protected $dependsOn = array();
    protected $md = './content/_stats.md'; // stats here
    private $statsPageMeta = array();
    
    public function onSinglePageLoading($id, &$skipPage)
    {
        if ($id === '_stats') { $skipFile = true; }
    }    
    
    public function onCurrentPageDiscovered(
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
        $currentPage = $this->getPico()->getCurrentPage();
        if ($currentPage !== null) {
			
            $file = fopen($this->md, 'c+');

            if (flock($file, LOCK_EX)) {
				
                $currentPageId = $this->getPico()->getCurrentPage()['id'];
				$this->loadStats($file);
                $this->statsPageMeta['stats'][$currentPageId]++;
                $this->saveStats($file);
                flock($file, LOCK_UN);
				
            }
			
            fclose($file);

        }
    }
   
   /*
    public function onPagesDiscovered(array &$pages)
    {
        $pages['_stats'] = [
            'id' => &$this->statsPageMeta['id'], // or 'id' => '_stats', fix 2
            'url' => &$this->statsPageMeta['url'],
            'title' => &$this->statsPageMeta['title'],
            'description' => &$this->statsPageMeta['description'],
            'author' => &$this->statsPageMeta['author'],
            'time' => &$this->statsPageMeta['time'],
            'hidden' => 'true', // fix 2
            'tags' => &$this->statsPageMeta['tags'], // fix 1
            'date' => &$this->statsPageMeta['date'],
            'date_formatted' => &$this->statsPageMeta['date_formatted'],
            'raw_content' => &$rawContent,
            'meta' => &$this->statsPageMeta
        ];
    }
*/
                  
    private function loadStats($file)
    {
        $currentPageId = $this->pico->getCurrentPage()['id'];
        // front matter YAML
		$fileLength = filesize($this->md);
        $frontMatter = $fileLength > 0 ? fread($file, $fileLength) : '';
        $frontMatterArray = $this->getPico()->parseFileMeta($frontMatter, []);        
        $this->statsPageMeta = $frontMatterArray;
        $this->statsPageMeta['stats'] = $this->statsPageMeta['stats'] ?? [];
		
		error_log('Number of IDs loaded: ' . count($this->statsPageMeta['stats']));
		
        if (!array_key_exists($currentPageId, $this->statsPageMeta['stats'])) {
            $this->statsPageMeta['stats'][$currentPageId] = 0;
        }                
    }
    
    private function saveStats($file)
    {
        // update the front matter
        $frontMatterArray = array('stats' => $this->statsPageMeta['stats']);
        $yaml = "---\n" . Yaml::dump($frontMatterArray) . "---\n";
		
		ftruncate ($file, 0);	// Empty the file
		rewind($file);			// Return to position 0
        fwrite($file, $yaml);
    } 
}
?>
