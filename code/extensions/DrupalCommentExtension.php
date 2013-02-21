<?php
/**
 * Should be applied to Comment.
 */
class DrupalCommentExtension extends DataExtension {

	static $db = array(
		'DrupalCid' => 'Int',
		'Subject' => 'Text',
		'Hostname' => 'Text',
		'Email' => 'Text',
	);
	
}