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
                $startDates = $arrExistingDates['StartDate'];
                $endDates = $arrExistingDates['EndDate'];
                $startTimes = $arrExistingDates['StartTime'];
                $endTimes = $arrExistingDates['EndTime'];
                foreach ($startDates as $id => $strDate) {
                    if ($date = TOOccurrence::get()->byID($id)) {
                        $date->StartDate = $startDates[$id];
                        $date->EndDate = $endDates[$id];
                        $date->StartTime = $startTimes[$id];
                        $date->EndTime = $endTimes[$id];
                        $items->push($date);
                    }
                }
            }
            if (isset($value['new'])) {
                $arrNewDates = $value['new'];
                $startDates = $arrNewDates['StartDate'];
                $endDates = $arrNewDates['EndDate'];
                $startTimes = $arrNewDates['StartTime'];
                $endTimes = $arrNewDates['EndTime'];
                $iCount = count($startDates);
                for ($i = 0; $i < $iCount; $i++) {
                    $item = TOOccurrence::create(array(
                        'StartDate' => $startDates[$i],
                        'EndDate'   => $endDates[$i],
                        'StartTime' => $startTimes[$i],
                        'EndTime'   => $endTimes[$i],
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
            $arrExistingDates = $arrFields['old'];
            $startDates = $arrExistingDates['StartDate'];
            $endDates = $arrExistingDates['EndDate'];
            $startTimes = $arrExistingDates['StartTime'];
            $endTimes = $arrExistingDates['EndTime'];
            foreach ($record->$relation() as $item) {
                $id = $item->ID;
                $item->StartDate = isset($startDates[$id]) ? $startDates[$id] : $item->StartDate;
                $item->EndDate = isset($endDates[$id]) ? $endDates[$id] : $item->EndDate;
                $item->StartTime = isset($startTimes[$id]) ? $startTimes[$id] : $item->StartDate;
                $item->EndTime = isset($endTimes[$id]) ? $endTimes[$id] : $item->StartDate;
                $item->write();
            }
        }
        if (isset($arrFields['new'])) {
            $arrNewDates = $arrFields['new'];
            $startDates = $arrNewDates['StartDate'];
            $endDates = $arrNewDates['EndDate'];
            $startTimes = $arrNewDates['StartTime'];
            $endTimes = $arrNewDates['EndTime'];
            $iCount = count($startDates);
            for ($i = 0; $i < $iCount; $i++) {
                if (!$startDates[$i]) break;

                $item = TOOccurrence::create(array(
                    'StartDate' => $startDates[$i],
                    'EndDate'   => $endDates[$i],
                    'StartTime' => $startTimes[$i],
                    'EndTime'   => $endTimes[$i],
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
        Requirements::javascript(TOM_DIR . '/javascript/admin/TOOccurrenceField.js');
        Requirements::css(TOM_DIR. '/css/TOOccurrenceField.css');

        return parent::Field();
    }

    public function getRecord() {
        if (!$this->record && $this->form) {
            if (($record = $this->form->getRecord()) && ($record instanceof DataObject)) {
                $this->record = $record;
            } elseif (($controller = $this->form->Controller())
                && $controller->hasMethod('data')
                && ($record = $controller->data())
                && ($record instanceof DataObject)
            ) {
                $this->record = $record;
            }
        }
        return $this->record;
    }

    public function StartDateField() {
        return DateField::create($this->getName(). '[new][StartDate][]', '')
            ->setConfig('showcalendar', true)
            ->addExtraClass('startdate')
            ->setAttribute('readonly', true);
    }

    public function EndDateField() {
        return DateField::create($this->getName(). '[new][EndDate][]', '')
            ->setConfig('showcalendar', true)
            ->addExtraClass('enddate')
            ->setAttribute('readonly', true);
    }

    public function StartTimeField() {
        return TOTimeField::create($this->getName(). '[new][StartTime][]', '')->addExtraClass('starttime');
    }

    public function EndTimeField() {
        return TOTimeField::create($this->getName(). '[new][EndTime][]', '')->addExtraClass('endtime');
    }

    public function IsMultipleOccurrencesAllowed() {
        return Config::inst()->get('TOSettings', 'allow_multiple_occurrences');
    }

    public function validate($validator) {
        $arrValue = $this->value;
        if (isset($arrValue['new']) && !isset($arrValue['old']) && !$arrValue['new']['StartDate'][0] && !$arrValue['new']['EndDate'][0]) {
            $validator->validationError($this->name, 'Please add an occurrence for this event.', "validation", false);
            return false;
        }
        return true;
    }

}