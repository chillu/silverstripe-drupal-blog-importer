# Drupal Blog Importer for the SilverStripe Blog Module

## Usage

### Export from Drupal

	mysqldump 

## Limitations

 * Blog post revision history is discarded
 * Blog post subscriptions by registered users are discarded
 * Blog post access control is ignored
 * Comment threads are flattened
 * Tags are not weighted, term hierarchies are flattened
 * View counts are not kept updated (unless specifically implemented)

## TODO

 * URL 