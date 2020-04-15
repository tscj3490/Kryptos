(function () {
    var app = angular.module('app', ['ngRoute', 'ngResource']);


    app.config(['$interpolateProvider', '$routeProvider', '$locationProvider', function ($interpolateProvider) {
        $interpolateProvider.startSymbol('<%');
        $interpolateProvider.endSymbol('%>');
    }]);


    app.controller('IndexCtrl', ['$scope', '$http', '$templateCache', function ($scope, $http) {
        $scope.persons = {};
        $scope.types = {};
        $scope.fields = {};
        $scope.form ={};
        $scope.writeType = {};
        $scope.writeField = {}

        $scope.groupItems = {};
        $scope.groupTemplateItems = [];
        $scope.updateGpTmpltShow = false;

       $http.get('/zbiory/get-persons').
            success(function (data) {
                $scope.persons = data;
            });

        //DuongTD - get group Items
        $http.get('/group-item/get-group-items').success(function (data) {
                $scope.groupItems = data;
                $scope.types = data;
                //$scope.form.groupItem = data;
        });
        
        $http.get('/group-item/get-Group-Template-Items').success(function (data) { 
                $scope.groupTemplateItems = data;
        });
        
        $scope.deleteGroupTmplt = function(id) {
            $http.post('/group-item/delete-Group-Template-Item', {id:id})
                 .success(function (data) {
                    if (data.status) {
                        setGritter(data.status, data.message);
                        $scope.groupTemplateItems = data.items;
                    } else {
                        setGritter(data.status, data.message);
                    }
            }).error(function (data, status, headers, config) {
                setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
            });
        }
        
        $scope.onUpdateGroupTmplt = function(data) {
            $scope.updateGpTmpltShow = true;
            $scope.updateGpTmplt_name = data.name;
            $scope.updateGpTmplt_id = data.id;
            
        }
        
        $scope.updateGroupTmplt = function(id, name) {
            $http.post('/group-item/update-Group-Template-Item', {id:id, name:name})
                 .success(function (data) {
                    if (data.status) {
                        setGritter(data.status, data.message);
                        $scope.groupTemplateItems = data.items;
                    } else {
                        setGritter(data.status, data.message);
                    }
            }).error(function (data, status, headers, config) {
                setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
            });
            
            $scope.updateGpTmpltShow = false;
        }

        if($('#formId').val() != '' && $('#formId').val() != null)
        {
            $http.get('/zbiory/get-form/id/' + $('#formId').val()).success(function (data) {
                $scope.form = JSON.parse(data.opis_pola_zbioru_ang);

            });
        };

        $scope.templateChecker = function(val){

            if($scope.form.person[val] != false && $('#formId').val() == '' || $('#formId').val() == null){
                var objType = {};
                var objField = {};

                objType[val] = JSON.parse(templateType);
                objField[val] = JSON.parse(templateField);
                _.extend($scope.writeType,objType);
                _.extend($scope.writeField,objField);
                $scope.form.type =$scope.writeType;
                $scope.form.field =$scope.writeField;
             }
        };


        //DuongTD
        $scope.groupItemChecker = function(val){

            if($scope.form.groupItem[val] != false && $('#formId').val() == '' || $('#formId').val() == null){
                var objType = {};
                var objField = {};

                objType[val] = JSON.parse(templateType);
                objField[val] = JSON.parse(templateField);
                _.extend($scope.writeType,objType);
                _.extend($scope.writeField,objField);
                $scope.form.type =$scope.writeType;
                $scope.form.field =$scope.writeField;
             }
        };
        $scope.addPerson=function(value){
            $scope.form.push({name: value});
        };

        $scope.setTypes = function (element, key) {

           $scope.typeModalKey = key;

            $http.get('/zbiory/get-types').success(function (data) {
                $scope.types = data;
            }).then(function () {
                $(element + '' + key).modal('show');
            });
        };

        $scope.setFields = function (id, element, pkey, tkey) {

            $scope.pModalKey = pkey;
            $scope.tModalKey = tkey;

            $http.get('/zbiory/get-fields/id/' + id).success(function (data) {
                $scope.fields = data;
                console.log($(element+'_'+pkey+'_'+tkey));
            }).then(function () {
                $(element+'_'+pkey+'_'+tkey).modal('show');
            });
        };

        $scope.clearGroupItems = function (groupItems) {
            
            if(!groupItems.length) {
                return;
            }
            
            var config = {params: {data: groupItems}};
            $http.post('/group-item/clear-Group-Items', config).success(function (data) {
                $scope.groupItems = data.groupItems;
                setGritter(data.status, data.message);
            }).error(function (data, status, headers, config) {
                setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
            });
            
        }
        
        $scope.saveGroupItems = function() {
            
        }
        
        $scope.save=function(){
            $http.post('/zbiory/save-form', $scope.form).success(function (data) {
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

        $scope.clearType=function(typeModalKey){
            $scope.form.type[typeModalKey] = {};
        };

        $scope.clearField=function(pModalKey,tModalKey){
            $scope.form.field[pModalKey][tModalKey] = {};
        }

        var templateType =  '{"PODSTAWOWE":1,"WRAŻLIWE":3,"DODATKOWE":2}';
        var templateField = '{"DODATKOWE":{"NR TELEFONU":14,"PODPIS":16,"E-MAIL":15},"PODSTAWOWE":{"MIEJSCE PRACY":12,"PESEL":10,"MIEJSCE URODZENIA":8,"IMIONA RODZICÓW":6,"ADRES ZAMIESZKANIA LUB POBYTU":3,"IMIĘ":1,"NAZWISKO":2,"ADRES KORESPONDENCYJNY":4,"DRUGIE IMIĘ":5,"DATA URODZENIA":7,"NIP":9,"NR DOWODU":11,"WYKSZTAŁCENIE":13},"WRAŻLIWE":{"PRZYNALEŻNOŚĆ WYZNANIOWA":17,"POCHODZENIE ETNICZNE":19,"PRZYNALEŻNOŚĆ ZWIĄZKOWA":21,"PRZEKONANIA RELIGIJNE":23,"ŻYCIE SEKSUALNE":25,"NAŁOGI":27,"DOTYCZĄCA SKAZAŃ":29,"DOT. ORZECZEŃ O UKARANIU":31,"LUB ADMINISTRACYJNYM":33,"DOTYCZĄCE INNYCH ORZECZEŃ W POSTĘPOWANIU SĄDOWYM":32,"DOTYCZĄCA MANDATÓW KARNYCH":30,"KOD GENETYCZNY":28,"STAN ZDROWIA":26,"PRZEKONANIA FILOZOFICZNE":24,"POGLĄDY POLITYCZNE":22,"PRZYNALEŻNOŚĆ PARTYJNA":20,"POCHODZENIE RASOWE":18}}';
    }]);

    app.controller('PersonIndex', ['$scope', '$http', function ($scope, $http) {

        $scope.addNewPerson = function () {
            if (_.indexOf($scope.persons, $scope.newPerson) === -1) {
                var config = {params: {data: $scope.newPerson}};
                $http.post('/zbiory/add-person', config).success(function (data) {
                        setGritter(data.status, data.message);
                        $scope.persons.push({id:data.id,nazwa:$scope.newPerson});
                        $scope.newPerson = '';
                }).error(function (data, status, headers, config) {
                    setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
                });
            }
        }
    }]);

    app.controller('FieldIndex', ['$scope', '$http', function ($scope, $http) {

        $scope.addNewField = function () {
            if (_.indexOf($scope.fields, $scope.newField) === -1) {
                var config = {params: {idType: $scope.newFieldTypeId, nazwa:$scope.newField}};
                $http.post('/zbiory/add-field?netbeans-xdebug', config).success(function (data) {
                    if (data.status) {
                        setGritter(data.status, data.message);
                        $scope.fields.push({id:data.id,nazwa:$scope.newField,s_zbiory_pola_typ_id:$scope.newFieldTypeId});
                        $scope.newField = '';
                        $scope.newFieldTypeId ='';
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
                        $scope.types.push({id:data.id, nazwa:$scope.newType});
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

    //DuongTD - Group Item Controller
    app.controller('GroupItems', ['$scope', '$http', function ($scope, $http) {

        $scope.addNewGroupItem = function () {
            if(!$scope.newGroupItem) {
                return;
            }
            
            if (_.indexOf($scope.groupItems, $scope.newGroupItem) === -1) {
                var config = {params: {data: $scope.newGroupItem}};
                $http.post('/group-item/add-group-item', config).success(function (data) {
                        setGritter(data.status, data.message);
                        $scope.groupItems.push({id:data.id,name:$scope.newGroupItem});
                        $scope.newGroupItem = '';
                }).error(function (data, status, headers, config) {
                    setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
                });
            }
        }
    }]);

})();
