(function () {
    var app = angular.module('app', ['ngRoute', 'ngResource']);

app.filter('removeSpecialChars', function () {
          return function (text) {
          return text.replace(/[^\w]/gi, '-');
        };
});

    app.config(['$interpolateProvider', '$routeProvider', '$locationProvider', function ($interpolateProvider) {
            $interpolateProvider.startSymbol('<%');
            $interpolateProvider.endSymbol('%>');
        }]);


    app.controller('IndexCtrl', ['$scope', '$http', '$templateCache', function ($scope, $http) {
            $scope.persons = {};
            $scope.types = {};
            $scope.fields = {};
            $scope.groups = {};
            $scope.form = {};
            $scope.groupType = {};
            $scope.groupItems = {};
            $scope.writeType = {};
            $scope.writeField = {};
            $scope.groupType = {};
//            var templateType = '{"PODSTAWOWE":1,"WRAŻLIWE":3,"DODATKOWE":2}';
//            var templateField = '{"DODATKOWE":{"NR TELEFONU":14,"PODPIS":16,"E-MAIL":15},"PODSTAWOWE":{"MIEJSCE PRACY":12,"PESEL":10,"MIEJSCE URODZENIA":8,"IMIONA RODZICÓW":6,"ADRES ZAMIESZKANIA LUB POBYTU":3,"IMIĘ":1,"NAZWISKO":2,"ADRES KORESPONDENCYJNY":4,"DRUGIE IMIĘ":5,"DATA URODZENIA":7,"NIP":9,"NR DOWODU":11,"WYKSZTAŁCENIE":13},"WRAŻLIWE":{"PRZYNALEŻNOŚĆ WYZNANIOWA":17,"POCHODZENIE ETNICZNE":19,"PRZYNALEŻNOŚĆ ZWIĄZKOWA":21,"PRZEKONANIA RELIGIJNE":23,"ŻYCIE SEKSUALNE":25,"NAŁOGI":27,"DOTYCZĄCA SKAZAŃ":29,"DOT. ORZECZEŃ O UKARANIU":31,"LUB ADMINISTRACYJNYM":33,"DOTYCZĄCE INNYCH ORZECZEŃ W POSTĘPOWANIU SĄDOWYM":32,"DOTYCZĄCA MANDATÓW KARNYCH":30,"KOD GENETYCZNY":28,"STAN ZDROWIA":26,"PRZEKONANIA FILOZOFICZNE":24,"POGLĄDY POLITYCZNE":22,"PRZYNALEŻNOŚĆ PARTYJNA":20,"POCHODZENIE RASOWE":18}}';

            angular.element(document).ready(function () {
                var zbiory_id = $('#zbiory_id').val();
                if(zbiory_id){
                $http.get('/zbiory/get-persons-by-zbiory-id/id/' + zbiory_id).
                        success(function (data) {

                            $scope.form.person = JSON.parse(data);

                            for (var key in $scope.form.person) {
                                (function (k) {
                                    
                                    $http.get('/zbiory/get-zbiory-person-template-typ/zbiory_id/' + zbiory_id + '/person_id/' + $scope.form.person[key]).
                                           then(function (result) {
                                               data = result.data;
                                                console.log("Hello -" + data);
                                                data = JSON.parse(data);
                                                console.log(data);
                                                $scope.templateMaker(k, data[0], data[1], data[2], data[3]);
                                            }).then(function () {
                                            setTimeout(function(){ 
                                                $('.js-ang-tab').find(':checkbox:not(:checked)').click();
                                            
                                            }, 3000);});
                                })(key);
                            }
                        });
                    }

            });

            $scope.templateMaker = function (val, tType, tFields, groups, items) {

                if ($scope.form.person[val] != false && $('#formId').val() == '' || $('#formId').val() == null) {
                    var objType = {};
                    var objField = {};
                    var objGroups = {};
                    var objItems = {};

                    objType[val] = tType;
                    objField[val] = tFields;
                    objGroups[val] = groups;
                    objItems[val] = items;
                    
                    _.extend($scope.writeType, objType);
                    _.extend($scope.writeField, objField);
                    _.extend($scope.groupType, objGroups);
                    _.extend($scope.groupItems, objItems);
                    
                    $scope.form.type = $scope.writeType;
                    $scope.form.field = $scope.writeField;
                    $scope.form.group = $scope.groupType;
                    $scope.form.item = $scope.groupItems;
                }
            };



            $http.get('/zbiory/get-persons').
                    success(function (data) {
                        $scope.persons = data;
                    });

            $http.get('/zbiory/get-groups').
                    success(function (data) {
                        $scope.groups = data;
                    });


            $http.get('/zbiory/get-types').success(function (data) {
                $scope.types = data;
            });



            if ($('#formId').val() != '' && $('#formId').val() != null)
            {
                $http.get('/zbiory/get-form/id/' + $('#formId').val()).success(function (data) {
                    $scope.form = JSON.parse(data.opis_pola_zbioru_ang);

                });
            }
            ;

            $scope.templateChecker = function (val) {

                if ($scope.form.person[val] != false && $('#formId').val() == '' || $('#formId').val() == null) {
                    var objType = {};
                    var objField = {};

                    objType[val] = JSON.parse(templateType);
                    objField[val] = JSON.parse(templateField);
                    _.extend($scope.writeType, objType);
                    _.extend($scope.writeField, objField);
                    $scope.form.type = $scope.writeType;
                    $scope.form.field = $scope.writeField;
                    
                }
            };





            $scope.addPerson = function (value) {
                $scope.form.push({name: value});
            };

            $scope.setTypes = function (element, key) {

                $scope.typeModalKey = key;
                $scope.typeModal = key.replace(/[^\w]/gi, '-');
                $http.get('/zbiory/get-types').success(function (data) {
                    $scope.types = data;
                }).then(function () {
                    $(element + '' + $scope.typeModal).modal('show');
                });
            };
            
              $scope.setGroups = function (element, key) {

                $scope.groupModalKey = key;
                $scope.groupModal = key.replace(/[^\w]/gi, '-');
                $http.get('/zbiory/get-groups').success(function (data) {
                    $scope.groups = data;
                }).then(function () {
                    $(element + '' + $scope.groupModal).modal('show');
                });
            };
            

            $scope.setFields = function (id, element, pkey, tkey) {

                $scope.pModalKey = pkey;
                $scope.tModalKey = tkey;
                
                $scope.pModal = pkey.replace(/[^\w]/gi, '-');
                $scope.tModal = tkey.replace(/[^\w]/gi, '-');

                $http.get('/zbiory/get-fields/id/' + id).success(function (data) {
                    $scope.fields = data;
                    console.log($(element + '_' + $scope.pModal + '_' + $scope.tModal));
                }).then(function () {
                    $(element + '_' + $scope.pModal + '_' + $scope.tModal).modal('show');
                });
            };
            
               $scope.setItems = function (id, element, pkey, tkey) {
                 console.log("set items");
                
                $scope.pItemModalKey = pkey;
                $scope.tItemModalKey = tkey;
                
                $scope.pItemModal = pkey.replace(/[^\w]/gi, '-');
                $scope.tItemModal = tkey.replace(/[^\w]/gi, '-');

                $http.get('/zbiory/get-items').success(function (data) {
                    $scope.items = data;
                    console.log($(element + '_' + $scope.pItemModal + '_' + $scope.tItemModal));
                }).then(function () {
                    $(element + '_' + $scope.pItemModal + '_' + $scope.tItemModal).modal('show');
                });
            };
            

            $scope.clearPerson = function () {
                $scope.form.person = {};
            };

            $scope.save = function () {
                $scope.form.id = $('#zbiory_id').val();
                $http.post('/zbiory/save-form', $scope.form).success(function (data) {
                    $('#zbiory_id').val(data.id);
                    if (data.status) {
                        setGritter(data.status, data.message);
                        $('#formId').val(data.id);
                    } else {
                        setGritter(data.status, data.message);
                    }
                }).error(function (data, status, headers, config) {
                    setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
                });
            };

            $scope.clearType = function (typeModalKey) {
                $scope.form.type[typeModalKey] = {};
            };

            $scope.clearField = function (pModalKey, tModalKey) {
                $scope.form.field[pModalKey][tModalKey] = {};
            }
            
             $scope.clearGroup = function (typeModalKey) {
                $scope.form.group[typeModalKey] = {};
            };
            
            $scope.clearItem = function (pModalKey, tModalKey) {
                $scope.form.item[pModalKey][tModalKey] = {};
            }

            var templateType = '{"PODSTAWOWE":1,"WRAŻLIWE":3,"DODATKOWE":2}';
            var templateField = '{"DODATKOWE":{"NR TELEFONU":14,"PODPIS":16,"E-MAIL":15},"PODSTAWOWE":{"MIEJSCE PRACY":12,"PESEL":10,"MIEJSCE URODZENIA":8,"IMIONA RODZICÓW":6,"ADRES ZAMIESZKANIA LUB POBYTU":3,"IMIĘ":1,"NAZWISKO":2,"ADRES KORESPONDENCYJNY":4,"DRUGIE IMIĘ":5,"DATA URODZENIA":7,"NIP":9,"NR DOWODU":11,"WYKSZTAŁCENIE":13},"WRAŻLIWE":{"PRZYNALEŻNOŚĆ WYZNANIOWA":17,"POCHODZENIE ETNICZNE":19,"PRZYNALEŻNOŚĆ ZWIĄZKOWA":21,"PRZEKONANIA RELIGIJNE":23,"ŻYCIE SEKSUALNE":25,"NAŁOGI":27,"DOTYCZĄCA SKAZAŃ":29,"DOT. ORZECZEŃ O UKARANIU":31,"LUB ADMINISTRACYJNYM":33,"DOTYCZĄCE INNYCH ORZECZEŃ W POSTĘPOWANIU SĄDOWYM":32,"DOTYCZĄCA MANDATÓW KARNYCH":30,"KOD GENETYCZNY":28,"STAN ZDROWIA":26,"PRZEKONANIA FILOZOFICZNE":24,"POGLĄDY POLITYCZNE":22,"PRZYNALEŻNOŚĆ PARTYJNA":20,"POCHODZENIE RASOWE":18}}';
        }]);



    app.controller('PersonIndex', ['$scope', '$http', function ($scope, $http) {

            $scope.addNewPerson = function () {
                if (_.indexOf($scope.persons, $scope.newPerson) === -1) {
                    var config = {params: {data: $scope.newPerson}};
                    $http.post('/zbiory/add-person', config).success(function (data) {
                        setGritter(data.status, data.message);
                        $scope.persons.push({id: data.id, nazwa: $scope.newPerson});
                        $scope.newPerson = '';
                    }).error(function (data, status, headers, config) {
                        setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
                    });
                }
            }
        }]);
    
        app.controller('GroupIndex', ['$scope', '$http', function ($scope, $http) {

            $scope.addNewGroup = function () {
                if (_.indexOf($scope.groups, $scope.newGroup) === -1) {
                    var config = {params: {data: $scope.newGroup}};
                    $http.post('/zbiory/add-group', config).success(function (data) {
                        setGritter(data.status, data.message);
                        $scope.groups.push({id: data.id, nazwa: $scope.newGroup});
                        $scope.newGroup = '';
                    }).error(function (data, status, headers, config) {
                        setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
                    });
                }
            }
        }]);
    

    app.controller('FieldIndex', ['$scope', '$http', function ($scope, $http) {

            $scope.addNewField = function () {
                if (_.indexOf($scope.fields, $scope.newField) === -1) {
                    var config = {params: {idType: $scope.newFieldTypeId, nazwa: $scope.newField}};
                    $http.post('/zbiory/add-field', config).success(function (data) {
                        if (data.status) {
                            setGritter(data.status, data.message);
                            $scope.fields.push({id: data.id, nazwa: $scope.newField, s_zbiory_pola_typ_id: $scope.newFieldTypeId});
                            $scope.newField = '';
                            $scope.newFieldTypeId = '';
                        } else {
                            setGritter(data.status, data.message);
                        }
                    }).error(function (data, status, headers, config) {
                        setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
                    });
                }
            }
        }]);

    app.controller('TypeIndex', ['$scope', '$http', function ($scope, $http) {

            $scope.addNewType = function () {
                if (_.indexOf($scope.types, $scope.newType) === -1) {
                    var config = {params: {data: $scope.newType}};
                    $http.post('/zbiory/add-type', config).success(function (data) {
                        if (data.status) {
                            setGritter(data.status, data.message);
                            $scope.types.push({id: data.id, nazwa: $scope.newType});
                            $scope.newType = '';
                        } else {
                            setGritter(data.status, data.message);
                        }
                    }).error(function (data, status, headers, config) {
                        setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
                    });
                }
            }
        }]);
    
    
        app.controller('ItemIndex', ['$scope', '$http', function ($scope, $http) {

             $scope.addNewItem = function () {
                if (_.indexOf($scope.items, $scope.newItem) === -1) {
                    var config = {params: {data: $scope.newItem}};
                    $http.post('/zbiory/add-item', config).success(function (data) {
                        if (data.status) {
                            setGritter(data.status, data.message);
                            $scope.items.push({id: data.id, nazwa: $scope.newItem});
                            $scope.newItem = '';
                        } else {
                            setGritter(data.status, data.message);
                        }
                    }).error(function (data, status, headers, config) {
                        setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
                    });
                }
            }
        }]);
    
    

    function setGritter(stat, message) {
        var text;
        if (message.length > 1) {
            for (var item in message) {
                text += message[item] + '<br/>';
            }
        } else {
            text = message;
        }
        if (stat) {
            $.gritter.add({
                title: 'Notice!',
                text: '<center>' + text + '</center>',
                image: false,
                sticky: false,
                time: ''
            });
        } else {
            for (var i in message) {
                var txt = '';
                for (var str in message[i]) {
                    txt += message[i][str] + '<br/>';
                }
                $.gritter.add({
                    title: i,
                    text: '<center>' + txt + '</center>',
                    image: false,
                    sticky: false,
                    time: ''
                });
            }
        }
    }

})();
