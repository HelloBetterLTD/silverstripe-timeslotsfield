<?php
/**
 * Created by PhpStorm.
 * User: priyashantha
 * Date: 9/8/17
 * Time: 12:47 PM
 */

class TimeSlotsField extends FormField
{

    protected $items;

    protected $itemsToBeDeleted;

    public function __construct($name, $title = null, SS_List $items = null) {
        parent::__construct($name, $title);
        if($items){
            $this->setItems($items);
        }
    }

    public function setValue($value, $record = null) {

        $items = new ArrayList();
        if(empty($value) && $record) {
            if(($record instanceof DataObject) && $record->hasMethod($this->getName())) {
                $data = $record->{$this->getName()}();
                if($data instanceof DataObject) {
                    $items->push($data);
                } elseif($data instanceof SS_List) {
                    $items = $data;
                }
            } elseif($record instanceof SS_List) {
                $items = $record;
            }
        } elseif (is_array($value)) {
            if (isset($value['delete']) && ($arrItemsToBeDeleted = $value['delete'])) {
                $fields = FieldList::create();
                foreach ($arrItemsToBeDeleted as $itemID) {
                    $fields->push(HiddenField::create($this->name. "[delete][$itemID]", '', $itemID));
                }
                $this->itemsToBeDeleted = $fields;
            }
            if (isset($value['old'])) {
                $timeSlots = $value['old'];
                foreach ($timeSlots as $id => $strDate) {
                    if ($date = TimeSlot::get()->byID($id)) {
                        $date->Time = $timeSlots[$id];
                        $items->push($date);
                    }
                }
            }
            if (isset($value['new'])) {
                $timeSlots = $value['new'];
                $iCount = count($timeSlots);
                for ($i = 0; $i < $iCount; $i++) {
                    $item = TimeSlot::create(array(
                        'Time' => $timeSlots[$i],
                        'Status'    => 'new'
                    ));
                    $items->push($item);
                }
            }
        }

        $this->items = $items;
        return parent::setValue($value);
    }


    public function setItems(SS_List $items) {
        return $this->setValue(null, $items);
    }

    public function getItemsToBeDeleted() {
        return $this->itemsToBeDeleted;
    }

    public function saveInto(DataObjectInterface $record) {
        $relation = $this->getName();
        $arrFields = $_POST[$relation];
        if (isset($arrFields['delete'])) {
            $arrToDelete = $arrFields['delete'];
            foreach ($arrToDelete as $itemID) {
                if ($item = $record->$relation()->byID($itemID))
                    $record->$relation()->remove($item);
            }
        }
        if (isset($arrFields['old'])) {
            $timeSlots = $arrFields['old'];
            foreach ($record->$relation() as $item) {
                $id = $item->ID;
                $item->Time = isset($timeSlots[$id]) ? $timeSlots[$id] : $item->Time;
                $item->write();
            }
        }
        if (isset($arrFields['new'])) {
            $timeSlots = $arrFields['new'];
            $iCount = count($timeSlots);
            for ($i = 0; $i < $iCount; $i++) {
                if (!$timeSlots[$i]) break;

                $item = TimeSlot::create(array(
                    'Time' => $timeSlots[$i],
                ));
                $itemID = $item->write();
                $record->$relation()->add($itemID);
            }
        }
    }


    public function getItems() {
        return $this->items ? $this->items : new ArrayList();
    }


    public function Field($properties = array()) {
        Requirements::javascript('timeslotsfield/javascript/TimeSlotsField.js');
        Requirements::css('timeslotsfield/css/TimeSlotsField.css');

        Config::inst()->update('i18n', 'time_format', 'HH:mm');
        return parent::Field();
    }

    public function getRecord() {
        if (!$this->record && $this->form) {
            if (($record = $this->form->getRecord()) && ($record instanceof DataObject)) {
                $this->record = $record;
            } elseif (($controller = $this->form->getController())
                && $controller->hasMethod('data')
                && ($record = $controller->data())
                && ($record instanceof DataObject)
            ) {
                $this->record = $record;
            }
        }
        return $this->record;
    }

    public function TimeField() {
        return TimePickerField::create($this->getName(). '[new][]', '');
    }

    public function validate($validator) {
        $arrValue = $this->value;
        if (isset($arrValue['new'])) {
            $arrTimes = array_merge($arrValue['old'], $arrValue['new']);
        } else {
            $arrTimes = $arrValue['old'];
        }
        $dups = self::get_duplicate_values(array_values($arrTimes));
        if (count($dups)) {
            $strDups = implode(', ', $dups);
            $validator->validationError($this->name, 'You can\'t have duplicate time slots. Please check "'.$strDups.'"', "validation", false);
            return false;
        }
        return true;
    }

    public static function get_duplicate_values($arr) {
        $dups = array();
        foreach(array_count_values($arr) as $val => $c)
            if($c > 1) $dups[] = $val;
        return $dups;
    }

}