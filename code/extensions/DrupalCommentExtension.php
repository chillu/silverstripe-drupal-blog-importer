<?php
/**
 * Should be applied to Comment.
 */
class DrupalCommentExtension extends DataExtension {

	private static $db = array(
		'DrupalCid' => 'Int',
		'Subject' => 'Text',
		'Hostname' => 'Text',
		'Email' => 'Text',
	);

	private static $has_one = array(
		'Author' => 'Member'
	);
	
}