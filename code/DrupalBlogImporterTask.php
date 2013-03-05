<?php
/**
 * Imports Drupal blog posts, comments, tags and users from CSV files.
 * Requires the input files to have certain columns, see README
 * for instructions how to export those correctly from your Drupal database.
 */
class DrupalBlogImporterTask extends BuildTask {

	protected $commentLoader;

	protected $userLoader;

	protected $postLoader;

	// TODO Doesn't work without DI on TaskRunner
	// static $dependencies = array(
	// 	'commentLoader' => '%$DrupalBlogCommentBulkLoader',
	// 	'userLoader' => '%$DrupalBlogUserBulkLoader',
	// 	'postLoader' => '%$DrupalBlogPostBulkLoader',
	// );
	
	public function run($request) {
		$postFile = $request->getVar('postFile');
		$userFile = $request->getVar('userFile');
		$commentFile = $request->getVar('commentFile');
		$doPublish = $request->getVar('publish');
		$doDownload = $request->getVar('download');
		$oldBaseUrl = $request->getVar('oldBaseUrl');
		$newBaseUrl = $request->getVar('newBaseUrl');

		if($doDownload && !$oldBaseUrl) {
			throw new InvalidArgumentException('Please specify a "oldBaseUrl" when using "download"=1');
		}

		// Import users: Needs to happen first so we can establish relations later
		if($userFile && file_exists($userFile)) {
			$this->log('Importing users...');
			$userResult = $this->getUserLoader()->load($userFile);
			$this->log(sprintf(
				'Created %d, updated %d, deleted %d',
				$userResult->CreatedCount(),
				$userResult->UpdatedCount(),
				$userResult->DeletedCount()
			));
		} else {
			$this->log(sprintf(
				'Skipping user import, no "userFile" found (path: "%s")',
				$userFile
			));
		}
		
		// Import blog posts
		if($postFile && file_exists($postFile)) {
			$this->log('Importing posts...');
			$postLoader = $this->getPostLoader();
			$postResult = $postLoader
				->setPublish($doPublish)
				->setOldBaseUrl($oldBaseUrl)
				->load($postFile);
			$this->log(sprintf(
				'Created %d, updated %d, deleted %d',
				$postResult->CreatedCount(),
				$postResult->UpdatedCount(),
				$postResult->DeletedCount()
			));

			if($doDownload && $images = $postLoader->getImages()) {
				$this->log(sprintf('Downloading %d assets for posts...', count($images)));
				$downloader = new DrupalBlogAssetDownloader();
				$batch = $downloader->download($images);
				if($exceptions = $batch->getExceptions()) {
					foreach($exceptions as $exception) {
						$this->log($exception->getMessage());
					}
				}
			}
		} else {
			$this->log(sprintf(
				'Skipping post import, no "postFile" found (path: "%s")',
				$postFile
			));
		}
		
		// Import comments
		if($commentFile && file_exists($commentFile)) {
			$this->log('Importing comments...');
			$commentResult = $this->getCommentLoader()->load($commentFile);
			$this->log(sprintf(
				'Created %d, updated %d, deleted %d',
				$commentResult->CreatedCount(),
				$commentResult->UpdatedCount(),
				$commentResult->DeletedCount()
			));
		} else {
			$this->log(sprintf(
				'Skipping comment import, no "commentFile" found (path: "%s")',
				$commentFile
			));
		}

		if($rules = $this->getPostLoader()->getRewriteRules()) {
			// Optionally prefix with a base url to the SilverStripe installation.
			// Useful if the old and new domains don't match, and the rewrite rules
			// need to be used on a different host.
			if($newBaseUrl) {
				foreach($rules as $old => $new) {
					$rules[$old] = trim($newBaseUrl, '/') . $new;
				}
			}
			$this->log('-----------------------------------------------');
			$this->log(sprintf("Rewrite rules for Apache: \n\n%s", $rules));
		}

		if($images = $this->getPostLoader()->getImages()) {
			$this->log('-----------------------------------------------');
			$this->log(sprintf("Image paths: \n\n%s", implode("\n", array_keys($images))));
		}
		
	}

	public function getCommentLoader() {
		if(!$this->commentLoader) $this->commentLoader = Injector::inst()->create('DrupalBlogCommentBulkLoader');
		return $this->commentLoader;
	}

	public function getPostLoader() {
		if(!$this->postLoader) $this->postLoader = Injector::inst()->create('DrupalBlogPostBulkLoader');
		return $this->postLoader;
	}

	public function getUserLoader() {
		if(!$this->userLoader) $this->userLoader = Injector::inst()->create('DrupalBlogUserBulkLoader');
		return $this->userLoader;
	}

	protected function log($msg) {
		echo $msg . "\n";
	}

}