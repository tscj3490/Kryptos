<?php

class Application_Service_EntityRow extends Zend_Db_Table_Row
{
    protected $_data_custom = [];

    /**
     * Retrieve row field value
     *
     * @param  string $columnName The user-specified column name.
     * @return string|null             The corresponding column value.
     */
    public function &__get($columnName)
    {
        $columnName = $this->_transformColumn($columnName);

        if (array_key_exists($columnName, $this->_data)) {
            return $this->_data[$columnName];
        }

        if (array_key_exists($columnName, $this->_data_custom)) {
            return $this->_data_custom[$columnName];
        }

        $z = null;

        return $z;
    }

    public function save(){
        $tableClass = $this->_tableClass;
        $tableObject = new $tableClass;
        $subscriptionsService = Application_Service_Subscriptions::getInstance();
        if ($subscriptionsService->checkLimit($tableClass, $tableObject->count())) {
            return parent::save();
        }else{
            throw new Application_SubscriptionOverLimitException();
        }
    }

    /**
     * Set row field value
     *
     * @param  string $columnName The column key.
     * @param  mixed $value The value for the property.
     * @return void
     */
    public function __set($columnName, $value)
    {
        $columnName = $this->_transformColumn($columnName);

        if (array_key_exists($columnName, $this->_data)) {
            if (!Application_Service_Utilities::equals($value, $this->_data[$columnName])) {
                $this->_modifiedFields[$columnName] = true;
            }
            $this->_data[$columnName] = $value;
        } else {
            $this->_data_custom[$columnName] = $value;
        }
    }

    /**
     * Unset row field value
     *
     * @param  string $columnName The column key.
     * @return Zend_Db_Table_Row_Abstract
     * @throws Zend_Db_Table_Row_Exception
     */
    public function __unset($columnName)
    {
        $columnName = $this->_transformColumn($columnName);

        if ($this->isConnected() && in_array($columnName, $this->_table->info('primary'))) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Specified column \"$columnName\" is a primary key and should not be unset");
        }

        if (array_key_exists($columnName, $this->_data)) {
            unset($this->_data[$columnName]);
        } elseif (array_key_exists($columnName, $this->_data_custom)) {
            unset($this->_data_custom[$columnName]);
        }

        return $this;
    }

    /**
     * Test existence of row field
     *
     * @param  string $columnName The column key.
     * @return boolean
     */
    public function __isset($columnName)
    {
        $columnName = $this->_transformColumn($columnName);

        return array_key_exists($columnName, $this->_data)
        || array_key_exists($columnName, $this->_data_custom);
    }

    public function getIterator()
    {
        return new ArrayIterator(array_merge((array)$this->_data, (array)$this->_data_custom));
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getModifiedFields()
    {
        return $this->_modifiedFields;
    }

    /**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
        if (!empty($this->_data)) {
            $entityData = [];
            $metadata = $this->_table->info('metadata');

            foreach ($this->_data as $column => $value) {
                if (isset($metadata[$column])) {
                    $entityData[$column] = $value;
                } else {
                    $this->_data_custom[$column] = $value;
                }
            }

            $this->_data = $entityData;

            if (!empty($this->_cleanData)) {
                $this->_cleanData = $entityData;
            }
        }
    }

    public function loadData($dataName)
    {
        $param = [$this];
        $this->getTable()->loadData($dataName, $param);
    }

    /**
     * Sets all data in the row from an array.
     *
     * @param  array $data
     * @return Zend_Db_Table_Row_Abstract Provides a fluent interface
     */
    public function setFromArray($dataObject)
    {
        if ($dataObject instanceof Zend_Db_Table_Row_Abstract) {
            $data = $dataObject->toArray();
        } else {
            $data = $dataObject;
        }

        $data = array_intersect_key($data, $this->_data);

        foreach ($data as $columnName => $value) {
            $this->__set($columnName, $value);
        }

        return $this;
    }

    /**
     * Returns the column/value data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge((array)$this->_data, (array)$this->_data_custom);
    }

    function __toString()
    {
        if (isset($this->display_name)) {
            return $this->display_name;
        }

        if (isset($this->name)) {
            return $this->name;
        }

        if (isset($this->title)) {
            return $this->title;
        }

        return '';
    }
}
