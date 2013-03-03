<?php
use Guzzle\Http\Client;
use Guzzle\Batch\BatchBuilder;

/**
 * Downloads images and other assets linked in blog post content,
 * and stores them in the local webroot. Batch-requests files in parallel.
 *
 * Caution: Does not filter or sanitize downloaded files or provided paths.
 */
class DrupalBlogAssetDownloader {

	/**
	 * @param  $map Absolute URLs to assets mapped to file paths
	 * relative to the SilverStripe webroot.
	 */
	public function download($map) {
		$client = $this->getClient();
		$builder = $this->getBatchBuilder();
		$batch = $builder->build();

		// Batch up requests
		foreach($map as $old => $new) {
			$batch->add($client->get($old));
		}

		// Execute requests
		$requests = $batch->flush();

		// Save files
		foreach($requests as $request) {
			$response = $request->getResponse();
			// TODO Handle redirects etc.
			if(!array_key_exists($response->getInfo('url'), $map)) continue;
			$newPath = BASE_PATH . '/'. $map[$response->getInfo('url')];
			Filesystem::makeFolder(dirname($newPath));
			file_put_contents($newPath, $response->getBody());
		}

		return $batch;
	}

	public function getClient() {
		return new Client();
	}

	public function getBatchBuilder() {
		return BatchBuilder::factory()
			->transferRequests(10)
			->bufferExceptions();
	}

}