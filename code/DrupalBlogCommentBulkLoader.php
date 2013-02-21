<?php
class DrupalBlogCommentBulkLoader extends CsvBulkLoader {

	public $columnMap = array(
		'nid' => 'nid',
		'cid' => 'cid',
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
	}

	protected function getPage($record) {
		return BlogEntry::get()->filter('DrupalNid', $record['nid'])->First();
	}

	protected function processRecord($record, $columnMap, &$result, $preview = false) {
		$page = $this->getPage($record);
		if(!$page) {
			throw new LogicException(sprintf(
				'No page #%s found for comment #%s',
				$record['nid'],
				$record['cid']
			));
		}
		$record['ParentID'] = $page->ID;
		$record['BaseClass'] = 'SiteTree';

		$objId = parent::processRecord($record, $columnMap, $result, $preview);
		$obj = Comment::get()->byId($objId);

		// Created gets overwritten on new records...
		$obj->Created = $record['Created'];
		$obj->write();
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