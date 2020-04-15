<?php

class Application_Service_HtmlHelper
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    public static function renderElement($params)
    {
        if (!empty($params['route'])) {
            $routeCheck = 'node/' . $params['route'];
            $routeParams = !empty($params['routeParams']) ? $params['routeParams'] : [];
            if (!Application_Service_Authorization::isGranted($routeCheck, $routeParams)) {
                return '';
            }
        }

        $class = [];
        $innerHtmlBefore = '';
        $innerHtml = '';
        $innerHtmlAfter = '';
        $attributes = [];
        $wrapper = [
            'class' => []
        ];
        $wrapper_attributes = [];

        // delete behaviour
        // should be before filters
        if (isset($params['attributes']['delete'])) {
            if (is_string($params['attributes']['delete'])) {
                $params['attributes']['route-attribute'] = 'data-href';
                if (empty($params['attributes']['confirm'])) {
                    $params['attributes']['confirm'] = $params['attributes']['delete'];
                }
            }
            unset($params['attributes']['delete']);
        }

        if (isset($params['attributes']['dialog'])) {
            $dialogAttributes = $params['attributes']['dialog'];

            if (is_bool($dialogAttributes)) {
                $class[] = 'choose-from-dial';
                $params['attributes']['route-attribute'] = 'data-dial-url';
            } elseif (is_array($dialogAttributes)) {
                $class[] = 'choose-from-dial';
                foreach ($dialogAttributes as $dialogAttributeName => $dialogAttributeValue) {
                    $dialogHtmlAttributeName = false;
                    switch ($dialogAttributeName) {
                        case "new-dialog":
                            $dialogHtmlAttributeName = 'data-new-dialog';
                            break;
                        case "ready":
                            $dialogHtmlAttributeName = 'data-dial-ready-fn';
                            break;
                        case "process":
                            $dialogHtmlAttributeName = 'data-dial-process-fn';
                            break;
                        case "data-process":
                            $dialogHtmlAttributeName = 'data-dial-data-process-fn';
                            break;
                        default:
                            vdie('Brak zdefiniowanego atrybutu');
                    }
                    if ($dialogHtmlAttributeName) {
                        $params['attributes'][$dialogHtmlAttributeName] = $dialogAttributeValue;
                    }
                }
                $params['attributes']['route-attribute'] = 'data-dial-url';
            }

            unset($params['attributes']['dialog']);
        }

        // filters:
        if (isset($params['attributes']['tooltip'])) {
            if (is_string($params['attributes']['tooltip'])) {
                $params['attributes']['data']['toggle'] = 'tooltip';
                if (empty($params['attributes']['title'])) {
                    $params['attributes']['title'] = $params['attributes']['tooltip'];
                }
            }
            unset($params['attributes']['tooltip']);
        }

        if (isset($params['attributes']['confirm'])) {
            if (is_string($params['attributes']['confirm'])) {
                $class[] = 'modal-confirm';
                if (empty($params['attributes']['data']['confirmation-class'])) {
                    $params['attributes']['data']['confirmation-class'] = $params['attributes']['confirm'];
                }
            }
            unset($params['attributes']['confirm']);
        }

        if (isset($params['attributes']['ajax'])) {
            if (is_bool($params['attributes']['ajax']) && $params['attributes']['ajax'] === true) {
                $class[] = 'ajax-operation';
                $params['attributes']['data']['ajax'] = 1;
                $params['attributes']['route-attribute'] = 'data-href';
            }
            unset($params['attributes']['ajax']);
        }

        if (isset($params['attributes']['relative'])) {
            if ($params['attributes']['relative'] === true) {
                $class[] = 'toggle-relative';
            } elseif (is_array($params['attributes']['relative'])) {
                $wrapper_attributes['data-relation-base'] = $params['attributes']['relative']['base_id'];
                $wrapper_attributes['data-relation-id'] = $params['attributes']['relative']['base_value'];
                $wrapper['class'][] = 'relative-element';

            }
            unset($params['attributes']['relative']);
        }
        // end filters

        if (isset($params['attributes']['data'])) {
            foreach ($params['attributes']['data'] as $k => $v) {
                $attributes['data-'.$k] = htmlspecialchars($v);
            }
            unset($params['attributes']['data']);
        }

        // deprecated:
        if (isset($params['attributes']['extra'])) {
            foreach ($params['attributes']['extra'] as $extra) {
                switch ($extra) {
                    case "tooltip":
                        $attributes['data-toggle'] = 'tooltip';
                        break;
                }
            }
            unset($params['attributes']['extra']);
        }
        // end deprecated

        if (!empty($params['route']) && (!isset($params['attributes']['href']) || isset($params['attributes']['route-attribute']))) {
            $routeParamsString = '';
            if (!empty($params['routeParams'])) {
                foreach ($params['routeParams'] as $routeParamName => $routeParamValue) {
                    $routeParamsString .= sprintf('/%s/%s', $routeParamName, $routeParamValue);
                }
            }
            $routeAttribute = isset($params['attributes']['route-attribute']) ? $params['attributes']['route-attribute'] : 'href';

            if (('href' !== $routeAttribute || 'a' === $params['tag']) && $routeAttribute !== false) {
                $attributes[$routeAttribute] = sprintf('/%s%s', $params['route'], $routeParamsString);
            }

            unset($params['attributes']['route-attribute']);
        }

        if (isset($params['attributes']['required'])) {
            if (is_bool($params['attributes']['required']) && $params['attributes']['required'] === true) {
                $class[] = 'validate[required]';
            }
            unset($params['attributes']['required']);
        }

        if (isset($params['attributes']['innerHtml'])) {
            $innerHtml = $params['attributes']['innerHtml'];
            unset($params['attributes']['innerHtml']);
        }

        if (isset($params['attributes']['icon'])) {
            $iconClass = '';
            $iconBehavior = '';
            switch ($params['attributes']['icon']) {
                case "send":
                    $iconClass = 'glyphicon glyphicon-share';
                    break;
                case "remove":
                case "delete":
                    $iconClass = 'glyphicon glyphicon-trash';
                    break;
                case "list":
                    $iconClass = 'glyphicon glyphicon-list';
                    break;
                case "print":
                    $iconClass = 'glyphicon glyphicon-print';
                    break;
                case "edit":
                    $iconClass = 'glyphicon glyphicon-pencil';
                    break;
                case "star":
                    $iconClass = 'glyphicon glyphicon-star';
                    break;
                case "move-up":
                    $iconClass = 'glyphicon glyphicon-chevron-up';
                    break;
                case "move-down":
                    $iconClass = 'glyphicon glyphicon-chevron-down';
                    break;
                case "plus":
                case "add":
                    $iconBehavior = 'fa';
                    $iconClass = 'plus';
                    break;
                case "download":
                    $iconBehavior = 'fa';
                    $iconClass = 'download';
                    break;
                case "view":
                    $iconBehavior = 'fa';
                    $iconClass = 'eye';
                    break;
                default:
                    Throw new Exception('Unhandled icon type');
            }
            if ($iconBehavior === 'fa') {
                if (!empty($iconClass)) {
                    $innerHtmlBefore .= sprintf('<i class="fa fa-%s"></i> &nbsp;', $iconClass);
                }
            } else {
                $class[] = $iconClass;
            }
            unset($params['attributes']['icon']);
        }

        if (isset($params['attributes']['class'])) {
            $class = array_merge($class, explode(' ', $params['attributes']['class']));
            unset($params['attributes']['class']);
        }

        if (!empty($params['attributes'])) {
            foreach ($params['attributes'] as $k => $v) {
                if ($v !== null && $v !== '') {
                    if(is_array($v)){
                        $v = '';
                    }
                    $attributes[$k] = htmlspecialchars($v);
                }
            }
        }

        if (!empty($class)) {
            $attributes['class'] = implode(' ', array_unique($class));
        }

        $tag = $params['tag'];
        $innerHtml = $innerHtmlBefore . $innerHtml . $innerHtmlAfter;
        $defaultTemplate = '';

        $singleElements = ['input', 'img', 'embed'];
        $isSingle = in_array($tag, $singleElements);

        switch ($tag) {
            case "bs.typeahead":
                if (!empty($attributes['multiple'])) {
                    $defaultTemplate = 'multichoose.';
                } else {
                    $defaultTemplate = 'typeahead.';
                }
                break;
            case "bs.varchar":
                $defaultTemplate = 'varchar.';
                break;
            case "bs.select":
                if (!empty($attributes['multiple'])) {
                    $defaultTemplate = 'checkboxes.';
                } else {
                    $defaultTemplate = 'select.';
                }
                break;
            case "bs.texthtml":
                $defaultTemplate = 'ckeditor.';
                break;
            case "bs.text":
                $defaultTemplate = 'text.';
                break;
            case "bs.dropzone":
                $defaultTemplate = 'dropzone.';
                break;
            case "bs.checkbox-line":
                $defaultTemplate = 'checkbox-line.';
                break;
        }

        if (!empty($wrapper_attributes)) {
            $wrapper['attributes_string'] = self::getAttributesString($wrapper_attributes);
        }
        $wrapper['class'] = implode(' ', $wrapper['class']);

        $template = sprintf('_reuse/form/%selement.html', !empty($params['template']) ? $params['template'] : $defaultTemplate);
        return Application_Service_Utilities::getInstance()->renderView($template, compact('tag', 'attributes', 'wrapper', 'innerHtml', 'isSingle', 'params'));
    }

    public static function getAttributesString($array)
    {
        $result = [];
        foreach ($array as $name => $value) {
            $result[] = sprintf('%s="%s"', $name, htmlspecialchars($value, ENT_COMPAT));
        }

        return implode(' ', $result);
    }

}