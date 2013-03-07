<?php
class DrupalBlogUserBulkLoader extends CsvBulkLoader {

	public $columnMap = array(
		'uid' => 'DrupalUid', // requires DrupalMemberExtension
		'title' => 'Nickname', // requires DrupalMemberExtension
		'mail' => 'Email',
		'name' => '->importName',
		'created' => 'Created',
		'changed' => 'LastEdited',
	);

	public $duplicateChecks = array(
		'DrupalUid' => array(
			'callback' => 'findDuplicateByUid'
		),
		'Nickname' => array(
			'callback' => 'findDuplicateByTitle'
		),
		'Email' => array(
			'callback' => 'findDuplicateByEmail'
		)
	);
	
	public function __construct($objectClass = 'Member') {
		parent::__construct($objectClass);
	}

	protected function importName($obj, $val, $record) {
		$parts = preg_split('/\s/', $val, 2);
		$obj->FirstName = $parts[0];
		if(isset($parts[1])) $obj->Surname = $parts[1];
	}

	protected function findDuplicateByEmail($email, $record) {
		if(!$email) return;

		return Member::get()->filter('Email', $email)->First();
	}

	protected function findDuplicateByUid($uid, $record) {
		if(!$uid) return;

		// Lookup is optional, fall back to title or email
		if(!singleton('Member')->hasDatabaseField($this->columnMap['uid'])) return;

		return Member::get()->filter($this->columnMap['uid'], $uid)->First();
	}

	protected function findDuplicateByTitle($title, $record) {
		if(!$title) return;

		// Lookup is optional, fall back to uid or email
		if(!singleton('Member')->hasDatabaseField($this->columnMap['title'])) return;

		return Member::get()->filter($this->columnMap['title'], $title)->First();
	}
	
}