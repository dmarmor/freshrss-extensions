<?php
class RssBridgeExtension extends Minz_Extension {
	public function init() {
		$this->registerHook('check_url_before_add',
			array($this, 'RssBridgeDetect'));
	}

	public function handleConfigureAction() {
		$this->registerTranslates();

		if (Minz_Request::isPost()) {
			FreshRSS_Context::$system_conf->rss_bridge_url =
				Minz_Request::param('rss_bridge_url', '');
			FreshRSS_Context::$system_conf->rss_bridge_token =
				Minz_Request::param('rss_bridge_token', '');
			FreshRSS_Context::$system_conf->save();
		}
	}

	public static function RssBridgeDetect($url) {
		$bridge_url = FreshRSS_Context::$system_conf->rss_bridge_url .
			'?action=detect&format=Atom&url=' . rawurlencode($url);
		
		// Add token if configured
		$token = FreshRSS_Context::$system_conf->rss_bridge_token ?? '';
		if (!empty($token)) {
			$token = 'token=' . rawurlencode($token);
			$bridge_url .= '&' . $token;
		}

		// Get both headers and response body
		$context = stream_context_create([
			'http' => [
				'timeout' => 10,
				'follow_location' => false, // Don't follow redirects automatically
				'ignore_errors' => true     // Get content even on error status codes
			]
		]);
		$response_body = file_get_contents($bridge_url, false, $context);
		$headers = $http_response_header ?? [];
		
		if (empty($headers)) {
			Minz_Log::warning('[RSS-Bridge extension] Failed to connect to RSS-Bridge at ' . FreshRSS_Context::$system_conf->rss_bridge_url);
		} else if (strpos($headers[0], '301') !== false) {
			// Parse Location header from response headers array
			$redirect_url = null;
			foreach ($headers as $header) {
				if (stripos($header, 'location:') === 0) {
					$redirect_url = trim(substr($header, 9)); // Remove "location:" prefix
					break;
				}
			}
			
			if ($redirect_url) {
				// Make relative URLs absolute
				if (strpos($redirect_url, 'http') !== 0) {
					$redirect_url = FreshRSS_Context::$system_conf->rss_bridge_url . $redirect_url;
				}
				
				// If we have a token and the redirect URL doesn't contain it, add it
				if (!empty($token) && strpos($redirect_url, 'token=') === false) {
					$separator = strpos($redirect_url, '?') !== false ? '&' : '?';
					$redirect_url .= $separator . $token;
				}
				
				// Parse bridge name from redirect URL
				$bridge_name = '';
				if (preg_match('/[?&]bridge=([^&]+)/', $redirect_url, $matches)) {
					$bridge_full_name = $matches[1];
					// Remove 'Bridge' suffix if it exists, otherwise use full name
					if (strtolower(substr($bridge_full_name, -6)) === 'bridge') {
						$bridge_name = substr($bridge_full_name, 0, -6);
					} else {
						$bridge_name = $bridge_full_name;
					}
					$bridge_name .= ' ';
				}
				
				Minz_Log::warning('[RSS-Bridge extension] ' . $bridge_name . 'URL detected! Created feed URL: ' . $redirect_url);
				return $redirect_url;
			} else {
				Minz_Log::warning('[RSS-Bridge extension] Got 301 redirect but no Location header found');
			}
		} else {
		
			// Extract error message from HTML response <p> tag
			$error_message = '';
			if (!empty($response_body) && preg_match('/<p[^>]*>(.*?)<\/p>/s', $response_body, $matches)) {
				$error_message = trim(strip_tags($matches[1]));
			}
			
			// Log specific response cases with extracted error messages
			if (strpos($headers[0], '401') !== false) {
				$log_msg = '[RSS-Bridge extension] Authentication failed: ';
				if ($error_message && stripos($error_message, 'token') !== false) {
					$log_msg .= $error_message;
				} else {
					$log_msg .= 'Server uses an unsupported authentication method';  //  (' . $error_message . ')
				}
				Minz_Log::warning($log_msg);
			} else if (strpos($headers[0], '404') !== false) {
				Minz_Log::warning('[RSS-Bridge extension] (404) RSS-Bridge endpoint not found - check configuration');
			} else if (strpos($headers[0], '200') !== false) {
				// Log when RSS-Bridge can't handle the URL (comment out if too noisy)
				$log_msg = '[RSS-Bridge extension] ' . ($error_message ?: '(200) RSS-Bridge unable to handle request for unknown reason');
				Minz_Log::warning($log_msg);
			} else {
				// Check if this was actually a connection failure
				if ($response_body === false) {
					$error = error_get_last();
					$error_message_raw = $error['message'] ?? 'unknown reason';
					Minz_Log::warning('[RSS-Bridge extension] Connection to RSS-Bridge endpoint failed: ' . $error_message_raw);
				} else {
					// Log other unexpected responses
					$log_msg = '[RSS-Bridge extension] Unexpected response: ' . trim($headers[0]);
					if ($error_message) {
						$log_msg .= ' (message: ' . $error_message . ')';
					}
					Minz_Log::warning($log_msg);
				}
			}
		}

		// Log that we're passing through the original URL (comment out if too noisy)
		Minz_Log::warning('[RSS-Bridge extension] Passing through original URL: ' . $url);
		
		return $url;
	}
}
