<?php

class VideoEmbedExtension extends Minz_Extension {
    public function init() {
        $this->registerHook('entry_before_display', [$this, 'embedVideo']);
        
        // Set default values if not already configured
        if ((FreshRSS_Context::$user_conf->videoembed_tiktok_enabled ?? null) === null) {
            FreshRSS_Context::$user_conf->videoembed_tiktok_enabled = true;
            FreshRSS_Context::$user_conf->save();
        }
        if ((FreshRSS_Context::$user_conf->videoembed_youtube_enabled ?? null) === null) {
            FreshRSS_Context::$user_conf->videoembed_youtube_enabled = true;
            FreshRSS_Context::$user_conf->save();
        }
        if ((FreshRSS_Context::$user_conf->videoembed_youtube_show_description ?? null) === null) {
            FreshRSS_Context::$user_conf->videoembed_youtube_show_description = false;
            FreshRSS_Context::$user_conf->save();
        }
    }
    
    public function handleConfigureAction() {
        $this->registerTranslates();
        
        if (Minz_Request::isPost()) {
            FreshRSS_Context::$user_conf->videoembed_tiktok_enabled = Minz_Request::param('tiktok_enabled', false) ? true : false;
            FreshRSS_Context::$user_conf->videoembed_youtube_enabled = Minz_Request::param('youtube_enabled', false) ? true : false;
            FreshRSS_Context::$user_conf->videoembed_youtube_show_description = Minz_Request::param('youtube_show_description', false) ? true : false;
            FreshRSS_Context::$user_conf->save();
        }
    }

    public function embedVideo($entry) {
        $url = $entry->link();
        $parsedUrl = parse_url($url);
        $host = strtolower($parsedUrl['host'] ?? '');
        
        // Handle TikTok URLs
        if (FreshRSS_Context::$user_conf->videoembed_tiktok_enabled && $this->isDomainMatch($host, 'tiktok.com')) {
            if (preg_match('#tiktok\.com/@[^/]+/video/(\d+)#', $url, $m)) {
                $videoId = $m[1];
                $embed = '<iframe src="https://www.tiktok.com/embed/'.$videoId.'" 
                           width="323" height="760" 
                           frameborder="0" allowfullscreen></iframe>';
                $entry->_content($embed);
                $this->removeEnclosures($entry);
            }
        }
        // Handle YouTube URLs
        elseif (FreshRSS_Context::$user_conf->videoembed_youtube_enabled && ($this->isDomainMatch($host, 'youtube.com') || $this->isDomainMatch($host, 'youtu.be'))) {
            $videoId = $this->extractYouTubeVideoId($url);
            if ($videoId) {
                $isShorts = strpos($url, '/shorts/') !== false;
                if ($isShorts) {
                    // YouTube Shorts - vertical format
                    $embed = '<iframe src="https://www.youtube.com/embed/'.$videoId.'" 
                               width="315" height="560" 
                               frameborder="0" allowfullscreen></iframe>';
                } else {
                    // Regular YouTube video - horizontal format  
                    $embed = '<iframe src="https://www.youtube.com/embed/'.$videoId.'" 
                               width="560" height="315" 
                               frameborder="0" allowfullscreen></iframe>';
                }
                
                // Extract and format description if enabled
                $formattedDescription = '';
                if (FreshRSS_Context::$user_conf->videoembed_youtube_show_description) {
                    $description = '';
                    $enclosures = $entry->enclosures();
                    if (!empty($enclosures)) {
                        foreach ($enclosures as $enclosure) {
                            if (($enclosure['description'] ?? '') !== '') {
                                $description = $enclosure['description'];
                                break; // Use first description found
                            }
                        }
                    }
                    
                    if ($description !== '') {
                        $formattedDescription = $this->formatDescriptionAsHtml($description);
                    }
                }
                
                // Set content with iframe and optional formatted description
                $content = $embed;
                if ($formattedDescription !== '') {
                    $content .= '<div class="video-description">' . $formattedDescription . '</div>';
                }
                $entry->_content($content);
                
                // Remove enclosures completely to eliminate link icon
                $this->removeEnclosures($entry);
            }
        }
        return $entry;
    }
    
    private function extractYouTubeVideoId($url) {
        // Handle various YouTube URL formats
        $patterns = [
            '#youtube\.com/watch\?v=([a-zA-Z0-9_-]+)#',      // youtube.com/watch?v=ID
            '#youtube\.com/embed/([a-zA-Z0-9_-]+)#',         // youtube.com/embed/ID
            '#youtube\.com/shorts/([a-zA-Z0-9_-]+)#',        // youtube.com/shorts/ID
            '#youtu\.be/([a-zA-Z0-9_-]+)#'                   // youtu.be/ID
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    private function removeEnclosures($entry) {
        // Remove enclosures to prevent thumbnail images from displaying
        $entry->_attribute('enclosures', []);
    }
    
    private function formatDescriptionAsHtml($description) {
        // Escape HTML entities first
        $html = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        
        // Convert URLs to clickable links
        $html = preg_replace(
            '#\b(https?://[^\s<>"\']+)#i',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $html
        );
        
        // Convert double newlines to paragraph breaks
        $html = preg_replace('#\n\n+#', '</p><p>', $html);
        
        // Convert single newlines to line breaks
        $html = str_replace("\n", '<br/>', $html);
        
        // Wrap in paragraph tags
        $html = '<p>' . $html . '</p>';
        
        // Clean up empty paragraphs
        $html = preg_replace('#<p></p>#', '', $html);
        
        return $html;
    }
    
    private function isDomainMatch($host, $targetDomain) {
        // Check for exact match or subdomain match
        return ($host === $targetDomain) || str_ends_with($host, '.' . $targetDomain);
    }
}
