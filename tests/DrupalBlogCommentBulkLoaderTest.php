<?php
class DrupalBlogCommentBulkLoaderTest extends SapphireTest {

	protected $requiredExtensions = array(
		'Member' => array('DrupalMemberExtension'),
		'BlogEntry' => array('DrupalBlogEntryExtension'),
		'Comment' => array('DrupalCommentExtension'),
	);

	public function testImport() {
		$loader = new DrupalBlogCommentBulkLoader();
		$result = $loader->load(BASE_PATH . '/drupal-blog-importer/tests/fixtures/comments.csv');
		
		$this->assertEquals(3, $result->CreatedCount());
		$this->assertEquals(0, $result->UpdatedCount());

		$created = $result->Created();
		$comment1 = $created->find('Subject', 'comment1 subject');
		$this->assertNotNull($comment1);
		
		$comment2 = $created->find('Subject', 'comment2 subject');
		$this->assertNotNull($comment2);
		
		$comment3 = $created->find('Subject', 'comment3 subject');
		$this->assertNotNull($comment3);
	}
	
}