<?php
class DrupalBlogUserBulkLoader extends CsvBulkLoader {
	
	public function __construct($objectClass = 'Member') {
		parent::__construct($objectClass);
	}
	
}