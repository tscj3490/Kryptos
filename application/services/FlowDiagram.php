<?php

class Application_Service_FlowDiagram {

    protected static $_instance = null;

    private function __clone() {
        
    }

    public static function getInstance() {
        return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance;
    }

    public function getDiagram($data, $events) {
        $flowDiagram = "";
        foreach ($data as $d) {
            if ($d->previous_event->id != '') {
                $flowDiagram .= 'op' . $d->previous_event->id . '["' . $d->previous_event->name . '"]';
                if ($d->bidirectional) {
                    $flowDiagram .= '---';
                } else {
                    $flowDiagram .= '-->';
                }

                if ($d->label != '') {
                    $flowDiagram .= '|' . $d->label . '|';
                }

                $flowDiagram .= 'op' . $d->event->id;
                if ($d->event->type_id == 1) {
                    $flowDiagram .= "{";
                } else {
                    $flowDiagram .= "[";
                }
                $flowDiagram .= $d->event->name;
                if ($d->event->type_id == 1) {
                    $flowDiagram .= "}\r\n";
                } else {
                    $flowDiagram .= "]\r\n";
                }
            }
        }
        $flowDiagram .= "\r\n";
        foreach ($events as $e) {
            $flowDiagram .= 'click op' . $e->id . ' "/flows-events/update/id/'.$e->id.'"'."\r\n";
        }
        
        return $flowDiagram;
    }
}
