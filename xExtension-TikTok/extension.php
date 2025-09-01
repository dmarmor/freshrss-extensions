<?php

class TikTokExtension extends Minz_Extension {
    public function init() {
        $this->registerHook('entry_before_display', [$this, 'cleanTikTok']);
    }

    public function cleanTikTok($entry) {
        $url = $entry->link();
        if (strpos($url, 'tiktok.com') !== false) {
            if (preg_match('#tiktok\.com/@[^/]+/video/(\d+)#', $url, $m)) {
                $videoId = $m[1];
                $embed = '<iframe src="https://www.tiktok.com/embed/'.$videoId.'" 
                           width="325" height="575" 
                           frameborder="0" allowfullscreen></iframe>';
                $entry->_content($embed);
            }
        }
        return $entry;
    }
}
