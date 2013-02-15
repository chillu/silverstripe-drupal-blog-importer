<?php
/**
 * Should be applied to BlogEntry.
 */
class DrupalBlogEntryExtension extends DataExtension {

	static $db = array(
		'DrupalNid' => 'Int',
	);
	
}