<?php

function smarty_function_file($params, Smarty_Internal_Template $template)
{
    $filesService = Application_Service_Files::getInstance();
    $file = Application_Service_Utilities::getModel('Files')->getOne($params['id']);

    $contentAfter = '';
    $contentBefore = '';
    $elementParams = [
        'attributes' => [],
    ];
    $tag = null;

    switch ($file['file_type']) {
        case "image/jpg":
        case "image/jpeg":
            $tag = 'img';
            if (empty($params['print']) || $params['print'] === 'preview_print') {
                $elementParams['attributes']['src'] = '/files/view/t/' . $file->token;
            } else {
                $elementParams['attributes']['src'] = $filesService->getFileRealPath($file->uri);
            }
            break;
        case "application/pdf":
            if ($params['print']) {
                $tag = 'span';
                //$elementParams['attributes']['class'] = 'elfinder-cwd-icon';
                //$elementParams['attributes']['style'] = 'background-image: url(file://'. Application_Service_Utilities::getFileRealPath('web/assets/plugins/elFinder.2.1.14/build/img/icons-big.png') .')';
                $contentAfter = sprintf('<span>%s</span>', $file->name);
                //vdie($elementParams);
            } else {
                $tag = 'embed';
                $elementParams['attributes']['src'] = '/files/view/t/' . $file->token;
                $elementParams['attributes']['width'] = '100%';
                $elementParams['attributes']['height'] = '400';
                $elementParams['attributes']['type'] = 'application/pdf';
            }

            //vdie($elementParams);
            break;
        default:
            vdie('Potrzeba określić wyświetlanie: '.$file['file_type']);

    }

    $elementParams['tag'] = $tag;

    if (!is_callable('smarty_function_element')) {
        require_once "function.element.php";
    }

    return $contentBefore . smarty_function_element($elementParams, $template) . $contentAfter;
}