<?php
class DrupalBlogUserBulkLoader extends CsvBulkLoader {

	public $columnMap = array(
		'uid' => 'DrupalUid', // requires DrupalMemberExtension
		'title' => 'Nickname', // requires DrupalMemberExtension
		'created' => 'Created',
		'changed' => 'LastEdited',
	);

	public $duplicateChecks = array(
		'DrupalUid' => array(
			'callback' => 'findDuplicateByUid'
		),
		'Nickname' => array(
			'callback' => 'findDuplicateByTitle'
		)
	);
	
	public function __construct($objectClass = 'Member') {
		parent::__construct($objectClass);

		if(
			!singleton($objectClass)->hasDatabaseField($this->columnMap['uid'])
			&& !singleton($objectClass)->hasDatabaseField($this->columnMap['title'])
		) {
			throw new LogicException(sprintf(
				'The user importer requires a unique identifier field for "uid" or "title" ' .
				'(expected "%s" or "%s")',
				$this->columnMap['uid'],
				$this->columnMap['title']
			));
		}
	}

	protected function findDuplicateByUid($uid, $record) {
		// Lookup is optional, fall back to title
		if(!singleton('Member')->hasDatabaseField($this->columnMap['uid'])) return;

		return Member::get()->filter($this->columnMap['uid'], $uid)->First();
	}

	protected function findDuplicateByTitle($title, $record) {
		// Lookup is optional, fall back to uid
		if(!singleton('Member')->hasDatabaseField($this->columnMap['title'])) return;

		return Member::get()->filter($this->columnMap['title'], $title)->First();
	}
	
}