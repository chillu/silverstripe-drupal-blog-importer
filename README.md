# Drupal Blog Importer for the SilverStripe Blog Module

[![Build Status](https://secure.travis-ci.org/chillu/silverstripe-drupal-blog-importer.png)](http://travis-ci.org/chillu/silverstripe-drupal-blog-importer)

Converts Drupal blog data into a SilverStripe blog (built with the 
[blog module](https://github.com/silverstripe/silverstripe-blog)).

 * Imports posts and tags in standard blog structures 
 * Imports comments with the ["comments" module](https://github.com/silverstripe/silverstripe-comments) (optional)
 * Imports blog authors and comment authors into `Member` records (optional)
 * Imports tags into many-many relationships created by the ["blogcategories" module](https://github.com/IOTI/silverstripe-blogcategories) (optional)
 * Import posts from different blogs into different "blog holders"
 * Based on available CSV data, no Drupal module installation or application access required
 * Supports incremental imports and updates
 * Generates Apache rewrite rules for post URLs

*Caution*: The module is in alpha status, and has only been tested against Drupal 5 so far.
It doesn't do a full import of every data point, see known limitations below.

## Setup

### SilverStripe Extensions

The SilverStripe blog and comments module don't provide all the columns necessary
to map the existing Drupal data, so we need to extend them by a few columns.
For example, Drupal comments have a subject line.
Add the following to your config (e.g. in `mysite/_config/config.yml`):

	BlogEntry:
	  extensions:
	    - DrupalBlogEntryExtension
	Comment:
	  extensions:
	    - DrupalCommentExtension
	Member:
	  extensions:
	    - DrupalMemberExtension

This is an optional step, and you'll need to adjust templates
and CMS logic to take advantage of those new columns.

### SQL Export Script

The export logic is placed in `tools/export.sql`.

The module operates on CSV data, which can be retrieved through the MySQL 
commandline tool. Please note that the SQL user needs to have been granted
`File` permissions, since the script uses `SELECT ... INTO OUTFILE`.

The script assumes your column type for blog nodes is called 'column'.
If its called something different (e.g. 'blog'), replace the value in the SQL script.

By default, the CSV data is exported to the `/tmp` folder. Change
the script paths if you don't have access to this location.
We're using specific joins and composite columns, so a straight CSV
export of the table data from your own tools won't work - please use this script.

## Usage

### Export from Drupal

Run the following command:

	mysql -u <user> -p <my-drupal-database> < drupal-blog-importer/tools/export.sql

### Import into SilverStripe

Run the following command in the SilverStripe webroot:

	sake dev/tasks/DrupalBlogImporterTask postFile=/tmp/posts.csv userFile=/tmp/users.csv commentFile=/tmp/comments.csv 

You can leave out arguments to only import partial data.

Available arguments:

 * `postFile`: Absolute or relative path of CSV file for blog posts
 * `commentFile`: Absolute or relative path of CSV file for comments
 * `userFile`: Absolute or relative path of CSV file for users
 * `publish`: Publish created blog pages?

## Extending the importer

The importer might not fully fit your requirements.
E.g, tags might be imported with the ["blogcategories" module]()
as many-many relationships, or comment authors imported with nicknames
matching their profile on the ["forum" module].

The CSV import logic is based on a core class, `CSVBulkLoader`.
It provides a lot of flexibility in transforming and remapping data.
Each type (users, comments, posts) has their own loader class, which can be subclassed.

	:::php
	class MyDrupalBlogPostBulkLoader extends DrupalBlogPostBulkLoader {
		public $columnMap = array(
			'tags' => '->importTags',
			// ...
		);
		protected function importTags($obj, $val, $record) {
			// Example: Look up many-many relation, and add new objects
		}
	}

Simply instruct the dependency injector to use those classes instead
(e.g. in `mysite/_config/injector.yml`):

	Injector:
		DrupalBlogPostBulkLoader:
			class: MyDrupalBlogPostBulkLoader


## Limitations

 * Blog post revision history is discarded
 * Blog post subscriptions by registered users are discarded
 * Blog post access control is ignored
 * Comment threads are flattened
 * Users don't have a public profile or the ability to log in
 * Tags are not weighted, term hierarchies are flattened
 * View counts are not kept updated (unless specifically implemented)