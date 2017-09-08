<?php
/**
 * Created by PhpStorm.
 * User: priyashantha
 * Date: 9/8/17
 * Time: 12:55 PM
 */

class TimeSlot extends DataObject
{

    private static $db = array(
        'Time' => 'Time'
    );

    public function getTitle()
    {
        return $this->dbObject('Time')->Nice();
    }

}