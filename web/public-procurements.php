<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
header('Content-Type: text/html; charset=utf-8');

$serverName = $_SERVER['SERVER_NAME'];

$apiUri = 'http://' . $serverName . ':8080/api/public-procurements';
$cacheFile = 'public-procurements.dat';
$disableCache = false;

if ($disableCache || !is_file($cacheFile)) {
    $json = file_get_contents($apiUri);
    file_put_contents($cacheFile, $json);
} else {
    $json = file_get_contents($cacheFile);
}

if (!$json) {
    exit;
}

$data = json_decode($json, true);
?>
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css" /> 
<style>
    td.details-control {
        background: url('https://datatables.net/examples/resources/details_open.png') no-repeat center center;
        cursor: pointer;
    }
    tr.shown td.details-control {
        background: url('https://datatables.net/examples/resources/details_close.png') no-repeat center center;
    }
</style>

<div class="header"><h1>Rejestr zamówień publicznych</h1></div>
<table id="registry" class="display" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th></th>
            <th>Numer</th>
            <th>Tytuł</th>
            <th>Data otwarcia</th>
            <th>Data zamknięcia</th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th></th>
            <th>Numer</th>
            <th>Tytuł</th>
            <th>Data otwarcia</th>
            <th>Data zamknięcia</th>
        </tr>
    </tfoot>
</table>

<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/plug-ins/1.10.13/sorting/natural.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        function format(d) {
            // `d` is the original data object for the row
            var result = '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
                    '<tr>' +
                    '<td>Nazwa i adres instytucji:</td>' +
                    '<td>' + d.institution_name.replace(/\n/g, "<br />") + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td>Przedmiot zamówienia:</td>' +
                    '<td>' + d.subject.replace(/\n/g, "<br />") + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td>Kryterium:</td>' +
                    '<td>' + d.criterion.replace(/\n/g, "<br />") + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td>Termin do którego należy składać oferty:</td>' +
                    '<td>' + d.date_due + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td>Numer podstępowania:</td>' +
                    '<td>' + d.procedure_number + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td>Data aktualizacji:</td>' +
                    '<td>' + d.updated_at + '</td>' +
                    '</tr>';

            if (d.ppattachments != null) {
                result += "<tr><td>Załączniki:</td></tr>"
                for (index = 0; index < d.ppattachments.length; ++index) {
                    result += "<tr>";
                    result += "<td><a target=\"_blank\" href=\"/file/" + d.ppattachments[index] + "\">" + d.ppattachments[index] + "</a></td>";
                    result += "</tr>"
                }
            }

            result = result + '</table>';

            return result;
        }

        var table = $('#registry').DataTable({
            "ajax": "<?php echo($apiUri); ?>",
            "language": {
                "processing": "Przetwarzanie...",
                "search": "Szukaj:",
                "lengthMenu": "Pokaż _MENU_ pozycji",
                "info": "Pozycje od _START_ do _END_ z _TOTAL_ łącznie",
                "infoEmpty": "Pozycji 0 z 0 dostępnych",
                "infoFiltered": "(filtrowanie spośród _MAX_ dostępnych pozycji)",
                "infoPostFix": "",
                "loadingRecords": "Wczytywanie...",
                "zeroRecords": "Nie znaleziono pasujących pozycji",
                "emptyTable": "Brak danych",
                "paginate": {
                    "first": "Pierwsza",
                    "previous": "Poprzednia",
                    "next": "Następna",
                    "last": "Ostatnia"
                },
                "aria": {
                    "sortAscending": ": aktywuj, by posortować kolumnę rosnąco",
                    "sortDescending": ": aktywuj, by posortować kolumnę malejąco"
                }
            },
                    columnDefs: [
       { type: 'natural', targets: '_all' }
     ],
            "columns": [
                {
                    "className": 'details-control',
                    "orderable": false,
                    "data": null,
                    "defaultContent": ''
                },
                {"data": "procurement_number"},
                {"data": "title"},
                {"data": "date_opened"},
                {"data": "date_closed"}
            ],
            "order": [[1, 'asc']]
        });
        $('#registry tbody').on('click', 'td.details-control', function () {
            var tr = $(this).closest('tr');
            var row = table.row(tr);

            if (row.child.isShown()) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
            } else {
                // Open this row
                row.child(format(row.data())).show();
                tr.addClass('shown');
            }
        });
    });


</script>
