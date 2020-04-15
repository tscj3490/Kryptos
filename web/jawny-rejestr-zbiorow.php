<?php

header('Content-Type: text/html; charset=utf-8');

$serverName = $_SERVER['SERVER_NAME'];

$apiUri = 'http://' . $serverName . '/api/jawny-rejestr-zbiorow';
$cacheFile = 'jawny-rejestr-zbiorow.dat';
$disableCache = true;

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
<style>
    .demo > div {
        width:1000px;
        margin:auto;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-element {
        box-sizing: border-box;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-element .zbior-naglowek > span:first-child +span {
        border-bottom:1px dashed #c7c0cb;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-element:last-child .zbior-naglowek > span:first-child +span {
        border-bottom:0;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-naglowek {
        background-color: #ff9900;
    }
    #kryptos-jawny-rejestr-zbiorow .list > thead {
        cursor:pointer;
        color:white;
        padding:10px;
        line-height:30px;
        box-sizing: border-box;
        background-color: #ff9900;
        font-weight: bold;
    }
    #kryptos-jawny-rejestr-zbiorow .list > tbody > tr > td:first-child {
        color: white;
        background-color: #ff9900;
        border-color: #ff9900;
    }
    #kryptos-jawny-rejestr-zbiorow .list > tbody > tr > td {
        cursor:pointer;
        padding:10px;
        line-height:30px;
        border-bottom: 1px dashed #c7c0cb;
    }
    #kryptos-jawny-rejestr-zbiorow .list > tbody > tr:last-child > td {
        border-bottom: 0;
    }
    #kryptos-jawny-rejestr-zbiorow .list > tbody > tr:hover > td + td {
        background: #f2f2f2;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-naglowek > span:first-child +span {
        margin-left:100px;
        display:block;
        min-height:50px;
        line-height:30px;
        padding:10px 0 10px 50px;
        box-sizing: border-box;
        background-color: white;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-dane {
        display:none;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-dane ul {
        list-style:none;
        padding:0;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-preview-wrapper {
        background:white;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-preview .zbior-dane {
        display:block;
    }
    #kryptos-jawny-rejestr-zbiorow table {
        border-collapse:collapse;
    }
    #kryptos-jawny-rejestr-zbiorow table td {
        border-collapse:collapse;
        padding:7px 10px;
    }
    #kryptos-jawny-rejestr-zbiorow table tr tr td {
        border-top:1px dashed #c7c0cb;
    }
    #kryptos-jawny-rejestr-zbiorow table td:first-child {
        padding:7px 4px;
    }
    #kryptos-jawny-rejestr-zbiorow .list {
        width: 100%;
    }
    #kryptos-jawny-rejestr-zbiorow .hidden {
        display:none;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-page {
        float:left;
        /*display:none;*/
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-page.active {
        /*display:block;*/
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-window {
        position:relative;
        overflow:hidden;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-window > div {
        position:absolute;
        top:0;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-preview {
        position:absolute;
        left:0;
        right:0;
        z-index: 9999;
        overflow: hidden;
        height:0;
        box-sizing: border-box;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-preview-close,
    #kryptos-jawny-rejestr-zbiorow .rejestr-paginator-next,
    #kryptos-jawny-rejestr-zbiorow .rejestr-paginator-prev {
        display:inline-block;
        padding: 0 10px;
        line-height:40px;
        height:40px;
        font-weight: bold;
        background-color: #ff9900;
        color:white;
        cursor:pointer;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-element {
        cursor: pointer;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-element:hover .zbior-naglowek > span:first-child + span {
        background: #f2f2f2;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-paginator {
        margin-top:25px;
        text-align:center;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-paginator-next {
        float:right;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-paginator-prev {
        float:left;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-paginator-summary {
        margin:0 100px;
        display:block;
        line-height:40px;
        height:40px;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-preview-navigation {
        text-align: center;
        padding:10px;
    }
    #kryptos-jawny-rejestr-zbiorow h1 {
        text-align:center;
        margin-bottom:40px;
    }
    #kryptos-jawny-rejestr-zbiorow .rejestr-current-page input {
        width:30px;
        height:30px;
        border:1px solid #ff9900;
        text-align:center;
    }
    #kryptos-jawny-rejestr-zbiorow .tab-header {
        background-color: #e8e8e8;
        color: #676767;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-naglowek2 {
        color: white;
        background-color: #ff9900;
        width: 1000px;
        display: table-cell;
        font-weight:bold;
    }
    #kryptos-jawny-rejestr-zbiorow .zbior-naglowek2 * {
        background-color: #ff9900 !important;
    }
</style>
<div class="demo">
    <div id="kryptos-jawny-rejestr-zbiorow">
        <div class="header"><h1>Jawny rejestr zbiorów danych osobowych</h1></div>
        <div id="opis">
            <h4>
                <span><?php echo($data['settings']['NAZWA ORGANIZACJI']); ?> - Rejestr zbiorów danych osobowych prowadzony na podstawie:</span>
                <ul>
                    <li>Art. 36a ust. ust. 2 pkt 2 ustawy z dnia 29 sierpnia 1997 r. o ochronie danych osobowych (tj. Dz. U. z 2016 r., poz. 922.)</li>
                    <li>Rozporządzenia Ministra Administracji i Cyfryzacji z dnia 11 maja 2015 r. w sprawie sposobu prowadzenia przez Administratora Bezpieczeństwa Informacji rejestru zbiorów danych ( Dz.U. 2015 poz. 719)</li>
                </ul>
            </h4>
        </div>
        <div class="rejestr-window">
            <div class="rejestr-scroller">
                <div class="rejestr-page active">
                    <table class="list">
                        <thead>
                        <tr>
                            <td>Nr zbioru</td>
                            <td>Nazwa zbioru danych osobowych</td>
                            <td>Data wpisania zbioru do rejestru</td>
                            <td>Data dokonania ostatniej aktualizacji</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($data['zbiory'] as $i => $zbior): ?>
                            <tr class="zbior-element <?= $i > 9 ? 'hidden' : '' ?>">
                                <td>ZDO <?= $zbior['id'] ?></td>
                                <td><?= $zbior['name'] ?></td>
                                <td><?= $zbior['data_wpisania'] ?></td>
                                <td>
                                    <?= $zbior['data_aktualizacji'] ?>
                                    <div class="hidden">
                                        <div class="zbior-dane">
                                            <table style="width: 100%">
                                                <tr class="tab-header">
                                                    <td>1.Nazwa zbioru danych:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= $zbior['name'] ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>2.Oznaczenie administratora danych i adres jego siedziby lub miejsca zamieszkania oraz numer identyfikacyjny rejestru podmiotów gospodarki narodowej, jeżeli został mu nadany:</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <table style="width: 100%">
                                                            <tr style="width: 50%">
                                                                <td class="administrator-table1">Administrator:</td>
                                                                <td class="administrator-table2"><?= $data['settings']['NAZWA ORGANIZACJI'] ?></td>
                                                                <td class="administrator-table1">REGON:</td>
                                                                <td class="administrator-table2"> <?= $data['settings']['REGON'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="administrator-table1">Miejscowość:</td>
                                                                <td class="administrator-table2"> <?= $data['settings']['ADRES MIEJSCOWOŚĆ'] ?></td>
                                                                <td class="administrator-table1">Kod pocztowy:</td>
                                                                <td class="administrator-table2"><?= $data['settings']['ADRES KOD'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="administrator-table1">Nr domu:</td>
                                                                <td class="administrator-table2"> <?= $data['settings']['ADRES NR DOMU'] ?></td>
                                                                <td class="administrator-table1">Nr lokalu:</td>
                                                                <td class="administrator-table2"><?= $data['settings']['ADRES NR LOKALU'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="administrator-table1">Ulica:</td>
                                                                <td class="administrator-table2"> <?= $data['settings']['ADRES ULICA'] ?></td>
                                                                <td class="administrator-table3"></td>
                                                                <td class="administrator-table3"></td>
                                                            </tr>

                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>3. Oznaczenie przedstawiciela administratora danych, o którym mowa w art. 3la ustawy i adres jego siedziby lub miejsca zamieszkania - w przypadku wyznaczenia takiego podmiotu:</td>
                                                </tr>
                                                <tr style="text-align: left">
                                                    <?php if($data['settings']['Nazwa przedstawiciela'] != ''){ ?>
                                                    <td><?= $data['settings']['Nazwa przedstawiciela'] ?>, <?= $data['settings']['PRZEDSTAWICIEL ADRES ULICA'] ?> <?= $data['settings']['PRZEDSTAWICIEL ADRES NR DOMU'] ?>/<?= $data['settings']['PRZEDSTAWICIEL ADRES NR LOKALU'] ?>, <?= $data['settings']['PRZEDSTAWICIEL ADRES KOD'] ?> <?= $data['settings']['PRZEDSTAWICIEL ADRES MIEJSCOWOŚĆ'] ?></td>
                                                    <?php }else{ ?>
                                                    <td>Nie dotyczy</td>
                                                    <?php } ?>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>4. Oznaczenie podmiotu, któremu powierzono przetwarzanie danych ze zbioru na podstawie art. 31 ustawy i adres jego siedziby lub miejsca zamieszkania w przypadku powierzenia przetwarzania danych temu podmiotowi:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= $zbior['entruster'] ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>5. Podstawa prawna upoważniająca do prowadzenia zbioru danych:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= $zbior['process_legal_basis'] ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>6. Cel przetwarzania danych w zbiorze:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= $zbior['process_purpose'] ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>7. Opis kategorii osób, których dane są przetwarzane w zbiorze:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= mb_strtolower($zbior['process_persons']) ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>8. Zakres danych przetwarzanych w zbiorze:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= mb_strtolower($zbior['process_metadata']) ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>9. Sposób zbierania danych do zbioru w szczególności informacja czy dane do zbioru są zbierane od osób, których dotyczą, czy z innych źródeł niż osoba, której dane dotyczą:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= mb_strtolower($zbior['collecting_description']) ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>10. Sposób udostępniania danych ze zbioru, w szczególności informacja czy dane ze zbioru są udostępniane  podmiotom innym niż upoważnione na podstawie przepisów prawa:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= mb_strtolower($zbior['sharing_description']) ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>11. Oznaczenie odbiorcy danych lub kategorii odbiorców, którym dane mogą być przekazywane:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= mb_strtolower($zbior['send_description']) ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>12. Informacja dotycząca ewentualnego przekazywania danych do państwa trzeciego:</td>
                                                <tr>
                                                    <td><?= $zbior['other_country_description'] ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>13. Data wpisania zbioru do rejestru:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= $zbior['data_wpisania'] ? $zbior['data_wpisania'] : 'Nie dotyczy' ?></td>
                                                </tr>
                                                <tr class="tab-header">
                                                    <td>14. Data dokonania ostatniej aktualizacji:</td>
                                                </tr>
                                                <tr>
                                                    <td><?= $zbior['data_aktualizacji'] ? $zbior['data_aktualizacji'] : 'Nie dotyczy' ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="rejestr-preview">
                <div class="rejestr-preview-wrapper">
                    <div class="rejestr-preview-navigation">
                        <span class="rejestr-preview-close">powrót</span>
                    </div>
                    <div class="rejestr-preview-data"></div>
                    <div class="rejestr-preview-navigation">
                        <span class="rejestr-preview-close">powrót</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="rejestr-paginator">
            <a class="rejestr-paginator-prev">poprzednia</a>
            <a class="rejestr-paginator-next">następna</a>
            <span class="rejestr-paginator-summary"><span class="rejestr-current-page"><input value="1"/></span> strona z <span class="rejestr-last-page">1</span></span>
        </div>
    </div>
</div>
<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script type="text/javascript">
    (function(){
        if (typeof jQuery !== 'undefined') {
            var $ = jQuery,
                currentPage = 1,
                pageMax = 10,
                rejestr = $('#kryptos-jawny-rejestr-zbiorow'),
                windowElement = rejestr.find('.rejestr-window'),
                windowScroller = windowElement.find('.rejestr-scroller'),
                windowPreview = windowElement.find('.rejestr-preview'),
                windowPreviewWrapper = windowElement.find('.rejestr-preview-wrapper'),
                cfgAnimationDuration = 300,
                cfgAnimationEasing = 'swing';

            windowPreview.find('.rejestr-preview-close').on('click', closePreview);
            windowPreview.hide();

            $('#kryptos-jawny-rejestr-zbiorow .zbior-element').not('.zbior-naglowek2').on('click', openPreview);
            $('#kryptos-jawny-rejestr-zbiorow .rejestr-current-page input').on('input keyup paste', changePage);

            function openPreview() {
                var dataElement = $(this).find('.zbior-dane'),
                    currentHeight = windowElement.height();

                windowPreviewWrapper.css('opacity', 0);
                windowPreview
                    .find('.rejestr-preview-data')
                    .html(dataElement.clone());
                windowPreview
                    .css('height', 'auto')
                    .show();

                var previewHeight = windowPreview.height();
                windowElement.animate({height: previewHeight}, cfgAnimationDuration, cfgAnimationEasing);
                windowPreview
                    .css('top', currentHeight / 2)
                    .animate({height:previewHeight, top: 0}, cfgAnimationDuration / 2, cfgAnimationEasing);
                windowPreviewWrapper.animate({opacity: 1}, cfgAnimationDuration / 2, cfgAnimationEasing);

            }
            function closePreview() {
                windowPreviewWrapper.animate({opacity: 0}, cfgAnimationDuration / 2, cfgAnimationEasing);
                windowPreview.animate({height: 0, top: windowScroller.children('.active').height() / 2}, cfgAnimationDuration / 2, cfgAnimationEasing, function() {
                    windowPreview.hide();
                });

                resizeWindow();
            }

            var nextButton = rejestr.find('.rejestr-paginator-next'),
                prevButton = rejestr.find('.rejestr-paginator-prev');

            var elements = $('.zbior-element'),
                contents = $('.zbior-dane'),
                elementsHeader = $('.rejestr-page table thead').children(),
                counter = elements.size(),
                lastPage = Math.ceil(counter / pageMax);

            rejestr.find('.rejestr-last-page').text(lastPage);

            distributePages();
            var windowPages = windowScroller.children();

            resizeWindow(true);

            if (counter > pageMax) {
                nextButton.show();
            }
            prevButton.hide();

            function distributePages() {
                var tmpPage = 1,
                    tmpElements,
                    pageElement,
                    elementStart;

                while (++tmpPage <= lastPage) {
                    elementStart = (tmpPage - 1) * pageMax;
                    tmpElements = elements.slice(elementStart, elementStart + pageMax);
                    pageElement = $('<div class="rejestr-page"><table class="list"><thead></thead><tbody></tbody></table></div>');
                    tmpElements
                        .appendTo(pageElement.find('tbody'))
                        .removeClass('hidden');
                    elementsHeader.clone().prependTo(pageElement.find('thead'));
                    pageElement.appendTo(windowScroller);
                }

                //elementsHeader.remove();
            }

            function resizeWindow(initialize) {
                var activeElement = windowScroller.children('.active'),
                    width = windowElement.width(),
                    height = activeElement.height();

                windowPages.css({
                    width: width
                });

                windowElement
                    .css({width: width});

                if (initialize) {
                    windowElement.css({height: height});
                } else {
                    windowElement.animate({height: height}, cfgAnimationDuration, cfgAnimationEasing);
                }

                windowScroller.css({
                    width: width * windowPages.size(),
                    height: height
                });
            }

            function setCurrentPageText() {
                rejestr.find('.rejestr-current-page input').val(currentPage);
            }

            function prevPage() {
                if (currentPage < 2) {
                    return;
                }

                currentPage--;
                windowScroller
                    .children('.active')
                    .removeClass('active')
                    .prev()
                    .addClass('active');
                windowScroller.animate({
                    left: '+=' +  windowElement.width()
                }, cfgAnimationDuration, cfgAnimationEasing, function() {
                    if (currentPage === 1) {
                        prevButton.hide();
                    }
                    nextButton.show();

                    setCurrentPageText();
                    resizeWindow();
                });

                closePreview();
            }

            function nextPage() {
                if (currentPage >= lastPage) {
                    return;
                }

                currentPage++;
                windowScroller
                    .children('.active')
                    .removeClass('active')
                    .next()
                    .addClass('active');
                windowScroller.animate({
                    left: '-=' +  windowElement.width()
                }, cfgAnimationDuration, cfgAnimationEasing, function() {
                    if (currentPage === lastPage) {
                        nextButton.hide();
                    }
                    prevButton.show();

                    setCurrentPageText();
                    resizeWindow();
                });

                closePreview();
            }

            function changePage() {
                var selectedPageValue = $(this).val();

                if (selectedPageValue === '') {
                    return;
                }
                var selectedPage = parseInt(selectedPageValue);

                if (selectedPageValue !== selectedPage.toString()) {
                    selectedPage = 1;
                }

                if (selectedPage < 0) {
                    selectedPage = 1;
                } else if (selectedPage > lastPage) {
                    selectedPage = lastPage;
                }

                var scrollByWidth = (currentPage - selectedPage) * windowElement.width();

                currentPage = selectedPage;
                $(this).val(currentPage);

                windowScroller
                    .children('.active')
                    .removeClass('active')
                    .end()
                    .children()
                    .eq(currentPage - 1)
                    .addClass('active');

                windowScroller.animate({
                    left: '+=' +  scrollByWidth
                }, cfgAnimationDuration, cfgAnimationEasing, function() {
                    prevButton.show();
                    nextButton.show();
                    if (currentPage === lastPage) {
                        nextButton.hide();
                    }
                    if (currentPage === 1) {
                        prevButton.hide();
                    }

                    setCurrentPageText();
                    resizeWindow();
                });
            }

            prevButton.click(prevPage);
            nextButton.click(nextPage);

        }
    })();
</script>
