<?php

/** @var Zend_Controller_Router_Rewrite $router */

/*$route = new Zend_Controller_Router_Route(
    'company-employees/:action/:mode/:id',
    array(
        'controller' => 'company-employees',
        'action' => 'index',
        'id' => null,
    ),
    array(
        'id' => '\d+',
    )
);
$router->addRoute('company_employees_all_mode', $route);

$route = new Zend_Controller_Router_Route(
    'company-employees/:action/:id',
    array(
        'controller' => 'company-employees',
        'action' => 'index',
        'id' => null,
    ),
    array(
        'id' => '\d+',
    )
);
$router->addRoute('company_employees_all', $route);*/

$route = new Zend_Controller_Router_Route(
    'company-employees/:companyId/:action/:mode/:id',
    array(
        'controller' => 'company-employees',
        'action' => 'index',
        'id' => null,
    ),
    array(
        'companyId' => '\d+',
        'id' => '\d+',
    )
);
$router->addRoute('company_employees_mode', $route);

$route = new Zend_Controller_Router_Route(
    'company-employees/:companyId/:action/:id',
    array(
        'controller' => 'company-employees',
        'action' => 'index',
        'id' => null,
    ),
    array(
        'companyId' => '\d+',
        'id' => '\d+',
    )
);
$router->addRoute('company_employees', $route);
 


/*

$route = new Zend_Controller_Router_Route_Regex(
    'company-employees/(\d+)/(\S+)/(.*)',
    array(
        'controller' => 'companyEmployees',
        'action' => 'index'
    ),
    array(
        1 => 'companyId',
        2 => 'action',
    )
);
$router->addRoute('company_employees', $route);


 */
