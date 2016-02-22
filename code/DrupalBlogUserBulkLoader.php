<?php
class DrupalBlogUserBulkLoader extends CsvBulkLoader
{

    public $columnMap = array(
        'uid' => 'DrupalUid', // requires DrupalMemberExtension
        'title' => 'Nickname', // requires DrupalMemberExtension
        'mail' => 'Email',
        'name' => '->importName',
        'created' => 'Created',
        'changed' => 'LastEdited',
    );

    public $duplicateChecks = array(
        'DrupalUid' => array(
            'callback' => 'findDuplicateByUid'
        ),
        'Nickname' => array(
            'callback' => 'findDuplicateByTitle'
        ),
        'Email' => array(
            'callback' => 'findDuplicateByEmail'
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
    
    public function __construct($objectClass = 'Member')
    {
        parent::__construct($objectClass);
    }

    protected function processRecord($record, $columnMap, &$result, $preview = false)
    {
        foreach ($this->listeners['beforeProcessRecord'] as $listener) {
            $listener($record, $columnMap, $result, $preview);
        }

        $objID = parent::processRecord($record, $columnMap, $result, $preview);
        $obj = Member::get()->byID($objID);

        foreach ($this->listeners['afterProcessRecord'] as $listener) {
            $listener($obj, $record, $columnMap, $result, $preview);
        }

        return $objID;
    }

    protected function importName($obj, $val, $record)
    {
        $parts = preg_split('/\s/', $val, 2);
        $obj->FirstName = $parts[0];
        if (isset($parts[1])) {
            $obj->Surname = $parts[1];
        }
    }

    protected function findDuplicateByEmail($email, $record)
    {
        if (!$email) {
            return;
        }

        return Member::get()->filter('Email', $email)->First();
    }

    protected function findDuplicateByUid($uid, $record)
    {
        if (!$uid) {
            return;
        }

        // Lookup is optional, fall back to title or email
        if (!singleton('Member')->hasDatabaseField($this->columnMap['uid'])) {
            return;
        }

        return Member::get()->filter($this->columnMap['uid'], $uid)->First();
    }

    protected function findDuplicateByTitle($title, $record)
    {
        if (!$title) {
            return;
        }

        // Lookup is optional, fall back to uid or email
        if (!singleton('Member')->hasDatabaseField($this->columnMap['title'])) {
            return;
        }

        return Member::get()->filter($this->columnMap['title'], $title)->First();
    }
}
