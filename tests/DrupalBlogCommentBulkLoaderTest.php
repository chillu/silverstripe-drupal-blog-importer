<?php
class DrupalBlogCommentBulkLoaderTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected $requiredExtensions = array(
        'Member' => array('DrupalMemberExtension'),
        'BlogEntry' => array('DrupalBlogEntryExtension'),
    );

    public function setUpOnce()
    {
        if (class_exists('Comment')) {
            Comment::add_extension('DrupalCommentExtension');
        }

        parent::setUpOnce();
    }

    public function testImport()
    {
        if (!class_exists('Comment')) {
            $this->markTestSkipped('"Comment" module not installed');
        }

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

    public function testCreatesNewMembers()
    {
        if (!class_exists('Comment')) {
            $this->markTestSkipped('"Comment" module not installed');
        }

        $loader = new DrupalBlogCommentBulkLoader();
        $result = $loader->load(BASE_PATH . '/drupal-blog-importer/tests/fixtures/comments.csv');

        $created = $result->Created();
        $comment1 = $created->find('Subject', 'comment1 subject');
        $this->assertEquals($comment1->Author()->DrupalUid, 201);
        
        $comment2 = $created->find('Subject', 'comment2 subject');
        $this->assertEquals($comment2->Author()->DrupalUid, 201);
        
        $comment3 = $created->find('Subject', 'comment3 subject');
        $this->assertEquals($comment3->Author()->DrupalUid, 202);
    }

    public function testLinksExistingMembers()
    {
        if (!class_exists('Comment')) {
            $this->markTestSkipped('"Comment" module not installed');
        }

        // Data matching comments.csv fixture
        $user1 = new Member(array('Nickname' => 'user1'));
        $user1->write();
        $user2 = new Member(array('Nickname' => 'user2'));
        $user2->write();

        $loader = new DrupalBlogCommentBulkLoader();
        $result = $loader->load(BASE_PATH . '/drupal-blog-importer/tests/fixtures/comments.csv');

        $created = $result->Created();
        $comment1 = $created->find('Subject', 'comment1 subject');
        $this->assertEquals($comment1->AuthorID, $user1->ID);
        
        $comment2 = $created->find('Subject', 'comment2 subject');
        $this->assertEquals($comment2->AuthorID, $user1->ID);
        
        $comment3 = $created->find('Subject', 'comment3 subject');
        $this->assertEquals($comment3->AuthorID, $user2->ID);
    }
}
