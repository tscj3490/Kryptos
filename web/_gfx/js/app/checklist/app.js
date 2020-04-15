(function () {
    var app = angular.module('app', ['ngRoute', 'ngResource']);


    app.config(['$interpolateProvider', '$routeProvider', '$locationProvider', function ($interpolateProvider) {
        $interpolateProvider.startSymbol('<%');
        $interpolateProvider.endSymbol('%>');
    }]);


    app.controller('IndexCtrl', ['$scope', '$http', '$templateCache', function ($scope, $http) {
       	$scope.checklists = {};
       	$scope.index = -1;
        //DuongTD - get group Items
        $http.get('/checklist/get-checklists').success(function (data) {
                $scope.checklists = data;
                //$scope.form.groupItem = data;
        });
		$scope.name = '';
		$scope.edit = true;
		$scope.error = false;
		$scope.incomplete = true; 
		$scope.edit_id = 0;
		$scope.edit_index = 0;
		$scope.editChecklist = function(index, id) {
			if (index == 'new') {
			    $scope.edit = true;
			    $scope.incomplete = false;
			    $scope.name = '';
			    $scope.edit_id = 0;
			    $scope.edit_index = 0;
			} else {
			    //$scope.edit = false;
			    $scope.edit_id = id;
			    $scope.edit_index = index;
			    $scope.incomplete = false;
			    $scope.name = $scope.checklists[index].name;
			}
		};


		$scope.SaveChecklist = function () {
			if($scope.name == null || $scope.name == '') {
				return;
			}
            if (_.indexOf($scope.checklists, $scope.name) === -1) {
            	if(!$scope.edit_id){
            		//addnew checklist
            		var config = {params: {data: $scope.name}};
            		var urlSave = '/checklist/add-new-checklist';
            	}else {
            		var config = {params: {name: $scope.name, id: $scope.edit_id}};
            		var urlSave = '/checklist/update-checklist';
            	}
                $http.post(urlSave, config).success(function (data) {
                        setGritter(data.status, data.message);
                        if(!$scope.edit_id){
                        	$scope.checklists.push({id:data.id,name:$scope.name});
                        }else{
                        	$scope.checklists = data.items;
                        }
                        $scope.name = '';
                }).error(function (data, status, headers, config) {
                    setGritter('error', data.message);
                });
            }
        }
        $scope.deleteChecklist = function(id) {
            $http.post('/checklist/delete-checklist', {id:id})
                 .success(function (data) {
                    if (data.status) {
                        setGritter(data.status, data.message);
                        $scope.checklists = data.items;
                    } else {
                        setGritter(data.status, data.message);
                    }
            }).error(function (data, status, headers, config) {
                setGritter('error', 'Coś poszło nie tak spróbuj ponownie');
            });
        }
        
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
    }]);
})();