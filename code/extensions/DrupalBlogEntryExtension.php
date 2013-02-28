<?php
/**
 * Should be applied to BlogEntry.
 */
class DrupalBlogEntryExtension extends DataExtension {

	static $db = array(
		'DrupalNid' => 'Int',
	);

	static $has_one = array(
		'Author' => 'Member'
	);
	
}