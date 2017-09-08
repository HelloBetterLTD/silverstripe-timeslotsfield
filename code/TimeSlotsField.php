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
                $arrExistingDates = $value['old'];
                $timeSlots = $arrExistingDates['Time'];
                foreach ($timeSlots as $id => $strDate) {
                    if ($date = TimeSlot::get()->byID($id)) {
                        $date->Time = $timeSlots[$id];
                        $items->push($date);
                    }
                }
            }
            if (isset($value['new'])) {
                $arrNewDates = $value['new'];
                $timeSlots = $arrNewDates['StartDate'];
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
            $arrExistingSlots = $arrFields['old'];
            $timeSlots = $arrExistingSlots['Time'];
            foreach ($record->$relation() as $item) {
                $id = $item->ID;
                $item->Time = isset($timeSlots[$id]) ? $timeSlots[$id] : $item->StartDate;
                $item->write();
            }
        }
        if (isset($arrFields['new'])) {
            $arrNewDates = $arrFields['new'];
            $timeSlots = $arrNewDates['Time'];
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
        Requirements::javascript('timeslotsfield/javascript/admin/TimeSlotsField.js');
        Requirements::css('timeslotsfield/css/TimeSlotsField.css');

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
        return TimeField::create($this->getName(). '[new][Time][]', '');
    }

}