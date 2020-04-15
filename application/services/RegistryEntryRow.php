<?php

class Application_Service_RegistryEntryRow extends Application_Service_EntityRow
{
    public function __toString()
    {
        return $this->title;
    }

    public function entityToString($entityId)
    {
        if (empty($this->entities[$entityId])) {
            return '';
        }

        $data = Application_Service_Utilities::forceArray($this->entities[$entityId]);
        $result = [];

        foreach ($data as $row) {
            $result[] = $row->__toString();
        }

        return implode(', ', $result);
    }
}
