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
		
		if($postFile && file_exists($postFile)) {
			$this->log('Importing posts...');
			$postResult = $this->getPostLoader()->setPublish($doPublish)->load($postFile);
			$this->log(sprintf(
				'Created %d, updated %d, deleted %d',
				$postResult->CreatedCount(),
				$postResult->UpdatedCount(),
				$postResult->DeletedCount()
			));
			$this->log(sprintf(
				"Rewrite rules for Apache: \n\n%s",
				$this->getPostLoader()->getRewriteRules()
			));
		} else {
			$this->log(sprintf(
				'Skipping post import, no "postFile" found (path: "%s")',
				$postFile
			));
		}
		// if($userFile && file_exists($userFile)) {
		// 	$this->log('Importing users...');
		// 	$userResult = $this->getUserLoader()->load($userFile);
		// } else {
		// 	$this->log(sprintf(
		// 		'Skipping user import, no "userFile" found (path: "%s")',
		// 		$userFile
		// 	));
		// }
		if($commentFile && file_exists($commentFile)) {
			$this->log('Importing comments...');
			$commentResult = $this->getCommentLoader()->load($commentFile);
		} else {
			$this->log(sprintf(
				'Skipping comment import, no "commentFile" found (path: "%s")',
				$commentFile
			));
		}
	}

	public function getCommentLoader() {
		if(!$this->commentLoader) $this->commentLoader = new DrupalBlogCommentBulkLoader();
		return $this->commentLoader;
	}

	public function getPostLoader() {
		if(!$this->postLoader) $this->postLoader = new DrupalBlogPostBulkLoader();
		return $this->postLoader;
	}

	public function getUserLoader() {
		if(!$this->userLoader) $this->userLoader = new DrupalBlogUserBulkLoader();
		return $this->userLoader;
	}

	protected function log($msg) {
		echo $msg . "\n";
	}

}