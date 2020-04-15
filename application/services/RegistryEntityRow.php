<?php

class Application_Service_RegistryEntityRow extends Application_Service_EntityRow
{
    function __toString()
    {
        $string = $this->value;

        if (!empty($this->base_object)) {
            if (!empty($this->base_object->display_name)) {
                $string = $this->base_object->display_name;
            } elseif (!empty($this->base_object->title)) {
                $string = $this->base_object->title;
            } elseif (!empty($this->base_object->nazwa)) {
                $string = $this->base_object->nazwa;
            }
        }

        if ($this->entity->entity->config_data->type === 'checkbox') {
            $string = $string ? 'tak' : 'nie';
        } elseif ($this->entity->entity->config_data->type === 'text') {
            $string = strip_tags($string);
            if (mb_strlen($string) > 100) {
                $string = mb_substr($string, 0, 100) . '…';
            }
        } elseif ($this->entity->entity->config_data->type === 'entry') {
            if ($this->entity->config_data->registry_id !== $this->entity->config_data->original_registry_id) {
                if ($this->entity->config_data->label_schema) {
                    Application_Service_Registry::getInstance()->entryGetEntities($this->base_object);
                    $string = Application_Service_Utilities::stempl($this->entity->config_data->label_schema, $this->base_object['entities_named']);
                }
            }
        }

        return $string;
    }
}
