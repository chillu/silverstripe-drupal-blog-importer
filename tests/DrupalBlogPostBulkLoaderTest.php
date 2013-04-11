<?php
class DrupalBlogPostBulkLoaderTest extends SapphireTest {

	protected $usesDatabase = true;

	protected $requiredExtensions = array(
		'Member' => array('DrupalMemberExtension'),
		'BlogEntry' => array('DrupalBlogEntryExtension'),
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

	public function testImages() {
		$loader = new DrupalBlogPostBulkLoader();
		$result = $loader->load(BASE_PATH . '/drupal-blog-importer/tests/fixtures/posts_with_images.csv');
		
		$this->assertEquals(3, $result->CreatedCount());

		$images = $loader->getImages();

		$this->assertArrayHasKey('/absolute/image.gif', $images);
		$this->assertArrayHasKey('relative/image.gif', $images);
		$this->assertArrayHasKey('relative/other_image.gif', $images);
		$this->assertArrayNotHasKey('http://myhost.com/image.gif', $images);
		$this->assertEquals('/assets/blog/absolute/image.gif', $images['/absolute/image.gif']);
		$this->assertEquals('/assets/blog/relative/image.gif', $images['relative/image.gif']);

		$created = $result->Created();
		$post2 = $created->find('Title', 'post2 title');
		$this->assertContains('src="/assets/blog/absolute/image.gif"', $post2->Content);
		$post3 = $created->find('Title', 'post3 title');
		$this->assertContains('src="/assets/blog/relative/image.gif"', $post3->Content);
	}

	public function testPublish() {
		$this->markTestIncomplete();
	}
	
}