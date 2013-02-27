<?php
class DrupalBlogPostBulkLoaderTest extends SapphireTest {

	protected $requiredExtensions = array(
		'Member' => array('DrupalMemberExtension'),
		'BlogEntry' => array('DrupalBlogEntryExtension'),
		'Comment' => array('DrupalCommentExtension'),
	);

	public function testImport() {
		$loader = new DrupalBlogPostBulkLoader();
		$result = $loader->load(BASE_PATH . '/drupal-blog-importer/tests/fixtures/posts.csv');
		
		$this->assertEquals(3, $result->CreatedCount());
		$this->assertEquals(0, $result->UpdatedCount());

		$created = $result->Created();
		$post1 = $created->find('Title', 'post1 title');
		$this->assertNotNull($post1);
		
		$post2 = $created->find('Title', 'post2 title');
		$this->assertNotNull($post2);
		
		$post3 = $created->find('Title', 'post3 title');
		$this->assertNotNull($post3);
	}

	public function testPublish() {
		$this->markTestIncomplete();
	}
	
}