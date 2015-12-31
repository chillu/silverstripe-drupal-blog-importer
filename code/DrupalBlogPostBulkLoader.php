<?php
use Guzzle\Http\Client;

/**
 * Optionally imports author information into an "Author" has_one relationship
 * on {@link BlogEntry}. The relationship needs to be added in custom code though.
 */
class DrupalBlogPostBulkLoader extends CsvBulkLoader
{
    
    /**
     * @var integer Create BlogHolder records on a specific tree element
     * (defaults to root node).
     */
    protected $parentId = 0;

    protected $urlMap = array();

    protected $images = array();

    /**
     * Path to which image links will be rewritten, relative
     * to SilverStripe webroot.
     * @var string
     */
    protected $imagePath = '/assets/blog';

    /**
     * Optional base URL for the old Drupal installation
     * in order to rewrite images effectively.
     * @var String
     */
    protected $oldBaseUrl;

    protected $publish = false;

    public $columnMap = array(
        'uid' => '->importAuthor',
        'nid' => 'DrupalNid', // requires the DrupalBlogEntryExtension
        'title' => 'Title',
        'body' => '->importContent',
        'changed' => 'LastEdited',
        'created' => '->importCreated',
        'tags' => '->importTags',
        'author_title' => 'Author',
    );

    public $duplicateChecks = array(
        'DrupalNid' => array(
            'callback' => 'findDuplicateByNid'
        )
    );

    /**
     * - beforeProcessRecord($record, $columnMap, $result, $preview)
     * - afterProcessRecord($obj, $record, $columnMap, $result, $preview)
     * 
     * @var array
     */
    public $listeners = array(
        'beforeProcessRecord' => array(),
        'afterProcessRecord' => array()
    );

    protected $_cache_tree;
    protected $_cache_holders = array();
    protected $_cache_categories = array();
    protected $_cache_memberByNickname = array();
    protected $_cache_memberByUid = array();

    public function __construct($objectClass = 'BlogEntry')
    {
        parent::__construct($objectClass);
    }

    protected function processRecord($record, $columnMap, &$result, $preview = false)
    {
        foreach ($this->listeners['beforeProcessRecord'] as $listener) {
            $listener($record, $columnMap, $result, $preview);
        }

        // Find or create a holder for this blog
        $holder = $this->getHolder($record);
        $record['ParentID'] = $holder->ID;

        $objID = parent::processRecord($record, $columnMap, $result, $preview);
        $obj = BlogEntry::get()->byID($objID);

        if ($this->getImagePath()) {
            $this->rewriteImages($obj, 'Content');
        }

        if ($this->publish) {
            $obj->publish('Stage', 'Live');
        }

        $this->urlMap[$record['dst']] = $obj->RelativeLink();

        foreach ($this->listeners['afterProcessRecord'] as $listener) {
            $listener($obj, $record, $columnMap, $result, $preview);
        }

        return $objID;
    }

    protected function rewriteImages($obj, $field)
    {
        preg_match_all('/<img[^>]*>/', $obj->$field, $imageTags, PREG_SET_ORDER);
        if ($imageTags) {
            foreach ($imageTags as $imageTag) {
                preg_match('/src=["\'](.+?)["\']/', $imageTag[0], $imageUrlMatch);
                if (!$imageUrlMatch) {
                    continue;
                }

                $oldImageUrl = $imageUrlMatch[1];
                $oldImageUrlNormalized = $this->normalizeImageUrl($oldImageUrl);
                
                // Ignore absolute urls since they'll continue to work
                if (Director::is_absolute_url($oldImageUrlNormalized)) {
                    continue;
                }

                // TODO Fix relative images
                $newImageUrl = rtrim($this->imagePath, '/')  . '/' . ltrim($oldImageUrlNormalized, '/');
                if ($this->getOldBaseUrl()) {
                    $oldImageUrlAbs = rtrim($this->getOldBaseUrl(), '/') . '/' . trim($oldImageUrlNormalized, '/');
                } else {
                    $oldImageUrlAbs = $oldImageUrlNormalized;
                }
                $this->images[$oldImageUrlAbs] = $newImageUrl;
                // TODO More robust replacement
                $obj->$field = str_replace($oldImageUrl, $newImageUrl, $obj->$field);
            }
            $obj->write();
        }
    }

    /**
     * Allows advanced image url handling.
     */
    protected function normalizeImageUrl($url)
    {
        if ($baseUrl = $this->getOldBaseUrl()) {
            $url = str_replace($baseUrl, '', $url);
        }
        
        return $url;
    }

    /**
     * @return BlogHolder
     */
    protected function getHolder($record)
    {
        $filter = new URLSegmentFilter();
        $urlSegment = $filter->filter($record['blog_path']);
        
        $tree = $this->_cache_tree;
        if (!$tree) {
            $tree = BlogTree::get()->filter(array(
                'Title' => 'Blogs'
            ))->First();
            if (!$tree) {
                $tree = new BlogTree(array(
                    'Title' => 'Blogs',
                    'ParentID' => $this->parentId,
                ));
                $tree->write();
                if ($this->publish) {
                    $tree->publish('Stage', 'Live');
                }
            }
            $this->_cache_tree = $tree;
        }

        $holder = (isset($this->_cache_holders[$urlSegment])) ? $this->_cache_holders[$urlSegment] : null;
        if (!$holder) {
            $holder = BlogHolder::get()->filter(array(
                'URLSegment' => $urlSegment,
                'ParentID' => $tree->ID
            ))->First();
            if (!$holder) {
                $holder = new BlogHolder(array(
                    'Title' => $record['blog_title'],
                    'URLSegment' => $urlSegment,
                    'ParentID' => $tree->ID,
                ));
                $holder->write();
                if ($this->publish) {
                    $holder->publish('Stage', 'Live');
                }
            }
            $this->_cache_holders[$urlSegment] = $holder;
        }

        return $holder;
    }

    /**
     * @return String Apache rewrite rules from a previous import.
     */
    public function getRewriteRules($newBaseUrl = null)
    {
        $rules = array();
        foreach ($this->urlMap as $before => $after) {
            $rules[] = sprintf(
                "RewriteRule ^%s %s [R=301,L]",
                $before,
                $after
            );
        }
        return implode("\n", $rules);
    }

    /**
     * @return BlogEntry
     */
    protected function findDuplicateByNid($nid, $record)
    {
        return BlogEntry::get()->filter('DrupalNid', $nid)->First();
    }

    protected function importContent($obj, $val, $record)
    {
        $obj->Content = $this->cleanupHtml($val);
    }

    protected function importCreated($obj, $val, $record)
    {
        $obj->Date = $val;
        $obj->Created = $val;
    }

    protected function importAuthor($obj, $val, $record)
    {
        $hasAuthorRelation = (bool)singleton('BlogEntry')->has_one('Author');
        $hasUidField = (bool)singleton('Member')->hasDatabaseField('DrupalUid');
        $hasNicknameField = (bool)singleton('Member')->hasDatabaseField('Nickname');

        if ($hasAuthorRelation && ($hasUidField || $hasNicknameField)) {
            $member = null;

            // Try importing by UID
            if (!$member && $hasUidField) {
                $member = (isset($this->_cache_memberByUid[$val])) ? $this->_cache_memberByUid[$val] : null;
                if (!$member) {
                    $member = Member::get()->filter('DrupalUid', $val)->First();
                    $this->_cache_memberByUid[$val] = $member;
                }
            }

            // Fall back to Nickname
            if (!$member && $hasNicknameField) {
                $member = (isset($this->_cache_memberByNickname[$val])) ? $this->_cache_memberByNickname[$val] : null;
                if (!$member) {
                    $member = Member::get()->filter('Nickname', $record['Author'])->First();
                    $this->_cache_memberByNickname[$val] = $member;
                }
            }

            // Fall back to creating a member
            if (!$member) {
                $member = new Member(array(
                    'DrupalUid' => $val,
                    'Nickname' => $record['Author'],
                ));
                $member->write();
            }
            if ($member) {
                // Save record in correct hierarchy first
                $holder = $this->getHolder($record);
                $obj->ParentID = $holder->ID;

                $obj->AuthorID = $member->ID;
                $obj->write();
            }
        } else {
            $obj->Author = $val;
        }
    }

    protected function importTags($obj, $val, $record)
    {
        if ($obj->many_many('BlogCategories')) {
            // Optionally import into many_many created by the "ioti/blogcategories" module
            $holder = $this->getHolder($record);
            $obj->ParentID = $holder->ID;
            $obj->write(); // required so relation setting works

            // Import to BlogCategory instead of tags text field
            $tags = explode(',', $val);
            $tags = array_map('trim', $tags);
            $obj->BlogCategories()->removeAll();
            foreach ($tags as $tag) {
                if (!$tag) {
                    continue;
                }

                $cat = (isset($this->_cache_categories[$tag])) ? $this->_cache_categories[$tag] : null;
                if (!$cat) {
                    $cat = BlogCategory::get()->filter(array(
                        'Title' => $tag,
                    ))->First();
                    if (!$cat) {
                        $cat = new BlogCategory(array(
                            'Title' => $tag
                        ));
                    }
                    $cat->write();
                    $this->_cache_categories[$tag] = $cat;
                }

                $obj->BlogCategories()->add($cat);

                // Not entirely accurate, since the title -> slug conversion rules 
                // are slightly different between SS and Drupal. Should catch the majority though.
                $this->urlMap['category/tag-list/' . $cat->URLSegment] = $cat->getLink();
            }
        } else {
            $tags = explode(',', $val);
            $tags = array_map('trim', $tags);
            $obj->Tags = implode(', ', $tags);
        }
    }

    /**
     * Remove certain HTML clutter, mostly from Word copypaste.
     */
    protected function cleanupHtml($val)
    {
        $val = preg_replace('/\s?style="[^"]*"/', '', $val);
        $val = preg_replace('/<font[^>]*>/', '', $val);
        $val = preg_replace('/<\/font[\s]*>/', '', $val);
        return $val;
    }

    public function setParentId($id)
    {
        $this->parentId = $id;
        return $this;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function setPublish($bool)
    {
        $this->publish = $bool;
        return $this;
    }

    public function getPublish()
    {
        return $this->publish;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImagePath($path)
    {
        $this->imagePath = $path;
        return $this;
    }

    public function getImagePath()
    {
        return $this->imagePath;
    }

    public function setOldBaseUrl($url)
    {
        $this->oldBaseUrl = $url;
        return $this;
    }

    public function getOldBaseUrl()
    {
        return $this->oldBaseUrl;
    }

    public function getUrlMap()
    {
        return $this->urlMap;
    }
}
