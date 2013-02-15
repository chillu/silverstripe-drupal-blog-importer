<?php
class DrupalBlogCommentBulkLoader extends CsvBulkLoader {
	
	public function __construct($objectClass = 'PageComment') {
		parent::__construct($objectClass);
	}
	
}