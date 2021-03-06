<?php
class DrupalBlogUserBulkLoaderTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected $requiredExtensions = array(
        'Member' => array('DrupalMemberExtension'),
        'BlogEntry' => array('DrupalBlogEntryExtension'),
    );

    public function testImport()
    {
        $loader = new DrupalBlogUserBulkLoader();
        $result = $loader->load(BASE_PATH . '/drupal-blog-importer/tests/fixtures/users.csv');
        $this->assertEquals(3, $result->CreatedCount());
        $this->assertEquals(0, $result->UpdatedCount());

        $created = $result->Created();
        $this->assertContains('user1', $created->column('Nickname'));
        $this->assertContains('user2', $created->column('Nickname'));
        $this->assertContains('user3', $created->column('Nickname'));
        $this->assertContains('201', $created->column('DrupalUid'));
        $this->assertContains('202', $created->column('DrupalUid'));
        $this->assertContains('203', $created->column('DrupalUid'));
        $this->assertContains('John', $created->column('FirstName'));
        $this->assertContains('Jane', $created->column('FirstName'));
        $this->assertContains('Jack', $created->column('FirstName'));
        $this->assertContains('Doe Smith', $created->column('Surname'));
    }
}
