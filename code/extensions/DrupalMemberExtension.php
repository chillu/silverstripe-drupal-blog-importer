<?php
/**
 * Should be applied to Member.
 */
class DrupalMemberExtension extends DataExtension
{

    private static $db = array(
        'DrupalUid' => 'Int',
        'Nickname' => 'Varchar(255)',
    );
}
