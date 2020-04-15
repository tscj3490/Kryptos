<div style="float: right">
<a href="#" class="glyphicon glyphicon-question-sign" data-toggle="modal" data-target="#myModal"></a>
    <div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
    <div class="modal-content" style="padding: 10px 20px 20px 20px; color: #505458">
      <div class="modal-header" style="text-align: center">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">{{$title}|translate}</h4>
      </div>
      <div class="modal-body">
        <p align="justify"style="text-indent: 5%;">{{$content}|translate}</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">{{'Zamknij'}|translate}</button>
      </div>
    </div>
    </div>
    </div>
</div>
<div style="clear: both"></div>