<?php
class DrupalBlogCommentBulkLoader extends CsvBulkLoader {

	public $columnMap = array(
		'nid' => 'nid',
		'cid' => 'cid',
		'uid' => '->importUser',
		'subject' => 'Subject', // requires DrupalCommentExtension
		'name' => 'Name',
		'mail' => 'Email',
		'comment' => '->importComment',
		'timestamp' => 'Created',
		'hostname' => 'Hostname', // requires DrupalCommentExtension
		'homepage' => 'URL',
	);

	public $duplicateChecks = array(
		'nid' => array(
			'callback' => 'findDuplicateByNid'
		)
	);
	
	public function __construct($objectClass = 'Comment') {
		parent::__construct($objectClass);

		if(!class_exists('Comment')) {
			throw new LogicException('The "comments" module is not installed, can not import comments');
		}
	}

	protected function getPage($record) {
		return BlogEntry::get()->filter('DrupalNid', $record['nid'])->First();
	}

	protected function processRecord($record, $columnMap, &$result, $preview = false) {
		$page = $this->getPage($record);
		if(!$page) {
			// Mainly for testing, in real imports the posts should be present already
			$holder = BlogHolder::get()->First();
			if(!$holder) {
				$holder = new BlogHolder();
				$holder->write();
			}
			$page = new BlogEntry(array(
				'DrupalNid' => $record['nid'],
				'ParentID' => $holder->ID
			));
			$page->write();
		}
		$record['ParentID'] = $page->ID;
		$record['BaseClass'] = 'SiteTree';

		$objId = parent::processRecord($record, $columnMap, $result, $preview);
		$obj = Comment::get()->byId($objId);

		// Created gets overwritten on new records...
		$obj->Created = $record['Created'];
		$obj->write();

		return $objId;
	}

	/**
	 * @return Comment
	 */
	protected function findDuplicateByNid($nid, $record) {
		$page = $this->getPage($record);
		if(!$page) return;

		return Comment::get()->filter(array(
			'Name' => $record['Name'],
			'Created' => $record['Created'],
			'ParentID' => $page->ID
		))->First();
	}

	protected function importComment($obj, $val, $record) {
		$obj->Comment = $this->cleanupHtml($val);
	}

	protected function importUser($obj, $val, $record) {
		if(!$val || !singleton('Member')->hasDatabaseField('DrupalUid')) return;

		// Try importing by UID
		$member = Member::get()->filter('DrupalUid', $val)->First();

		// Fall back to Nickname
		if(!$member) $member = Member::get()->filter('Nickname', $record['Name'])->First();

		// Fall back to creating a member
		if(!$member) {
			$member = new Member(array(
				'DrupalUid' => $val,
				'Nickname' => $record['Name'],
			));
			$member->write();
		}
		if($member) {
			$obj->AuthorID = $member->ID;
			$obj->write();
		}
	}

	/**
	 * Remove certain HTML clutter, mostly from Word copypaste.
	 */
	protected function cleanupHtml($val) {
		$val = preg_replace('/\s?style="[^"]*"/', '', $val);
		$val = preg_replace('/<font[^>]*>/', '', $val);
		$val = preg_replace('/<\/font[\s]*>/', '', $val);
		return $val;
	}
	
}