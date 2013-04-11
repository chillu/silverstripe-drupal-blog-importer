<?php
/**
 * Should be applied to BlogEntry.
 */
class DrupalBlogEntryExtension extends DataExtension {

	private static $db = array(
		'DrupalNid' => 'Int',
	);

	private static $has_one = array(
		'Author' => 'Member'
	);

	private static $indexes = array(
		'DrupalNid' => true,
	);
	
}