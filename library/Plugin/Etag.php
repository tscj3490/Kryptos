<?php
class Plugin_Etag extends Zend_Controller_Plugin_Abstract
{

	public function dispatchLoopShutdown()
	{
		$send_body = true;
		$etag = '"' . md5($this->getResponse()->getBody()) . '"';
		$inm = explode(',', getenv("HTTP_IF_NONE_MATCH"));
		$inm = str_replace('-gzip', '', $inm);
		// If the request would, without the If-None-Match header field,
		// result in anything other than a 2xx or 304 status,
		// then the If-None-Match header MUST be ignored
		$response_code = $this->getResponse()->getHttpResponseCode();

		if (($response_code > 200 && $response_code < 206) || ($response_code == 304)) {
			foreach ($inm as $i) {
				if (trim($i) == $etag) {
					$this->getResponse()
					->clearAllHeaders()
					->setHttpResponseCode(304)
					->clearBody();
					$send_body = false;
					break;
				}
			}
		}

		$this->getResponse()
				->setHeader('Cache-Control', 'max-age=7200, must-revalidate', true)
				->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + 2 * 3600) . ' GMT', true)
				->clearRawHeaders();

		if ($send_body) {
			$this->getResponse()
			->setHeader('Content-Length', strlen($this->getResponse()->getBody()));
		}
		$this->getResponse()->setHeader('ETag', $etag, true);
		$this->getResponse()->setHeader('Pragma', '');
	}
}