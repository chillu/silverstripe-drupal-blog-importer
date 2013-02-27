<?php
class DrupalBlogPostBulkLoader extends CsvBulkLoader {
	
	/**
	 * @var integer Create BlogHolder records on a specific tree element
	 * (defaults to root node).
	 */
	protected $parentId = 0;

	protected $urlMap = array();

	protected $publish = false;

	public $columnMap = array(
		'nid' => 'DrupalNid', // requires the DrupalBlogEntryExtension
		'title' => 'Title',
		'body' => '->importContent',
		'changed' => 'LastEdited',
		'created' => '->importCreated',
		'tags' => '->importTags',
		'author_title' => '->importAuthor',
	);

	public $duplicateChecks = array(
		'DrupalNid' => array(
			'callback' => 'findDuplicateByNid'
		)
	);

	public function __construct($objectClass = 'BlogEntry') {
		parent::__construct($objectClass);
	}

	protected function processRecord($record, $columnMap, &$result, $preview = false) {
		// Find or create a holder for this blog
		$holder = $this->getHolder($record);
		$record['ParentID'] = $holder->ID;

		$objID = parent::processRecord($record, $columnMap, $result, $preview);
		$obj = BlogEntry::get()->byID($objID);

		if($this->publish) $obj->publish('Stage', 'Live');

		$this->urlMap[$record['dst']] = $obj->RelativeLink();

		return $objID;
	}

	/**
	 * @return BlogHolder
	 */
	protected function getHolder($record) {
		$filter = new URLSegmentFilter();
		$urlSegment = $filter->filter($record['blog_path']);
		$tree = BlogTree::get()->filter(array(
			'Title' => 'Blogs'
		))->First();
		if(!$tree) {
			$tree = new BlogTree(array(
				'Title' => 'Blogs',
				'ParentID' => $this->parentId,
			));
			$tree->write();
			if($this->publish) $tree->publish('Stage', 'Live');
		}

		$holder = BlogHolder::get()->filter(array(
			'URLSegment' => $urlSegment,
			'ParentID' => $tree->ID
		))->First();
		if(!$holder) {
			$holder = new BlogHolder(array(
				'Title' => $record['blog_title'],
				'URLSegment' => $urlSegment,
				'ParentID' => $tree->ID,
			));
			$holder->write();
			if($this->publish) $holder->publish('Stage', 'Live');
		}

		return $holder;
	}

	/**
	 * @return String Apache rewrite rules from a previous import.
	 */
	public function getRewriteRules() {
		$rules = array();
		foreach($this->urlMap as $before => $after) {
			$rules[] = sprintf(
				"RewriteRule ^%s %s [R=301,L]",
				$before,
				$after
			);
		}
		return implode("\n", $rules);
	}

	/**
	 * @return BlogEntry
	 */
	protected function findDuplicateByNid($nid, $record) {
		return BlogEntry::get()->filter('DrupalNid', $nid)->First();
	}

	protected function importContent($obj, $val, $record) {
		$obj->Content = $this->cleanupHtml($val);
	}

	protected function importCreated($obj, $val, $record) {
		$obj->Date = $val;
		$obj->Created = $val;
	}

	protected function importAuthor($obj, $val, $record) {
		$obj->Author = $val;
	}

	protected function importTags($obj, $val, $record) {
		$tags = explode(',', $val);
		$tags = array_map('trim', $tags);
		$obj->Tags = implode(', ', $tags);
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

	public function setParentId($id) {
		$this->parentId = $id;
		return $this;
	}

	public function getParentId() {
		return $this->parentId;
	}

	public function setPublish($bool) {
		$this->publish = $bool;
		return $this;
	}

	public function getPublish() {
		return $this->publish;
	}
	
}