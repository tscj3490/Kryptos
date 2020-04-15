<?php

require_once ROOT_PATH . '/library/PHPExcel/Settings.php';

class Application_Service_Excel
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }

    public $excelObject;

    private function __construct()
    {
        PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
    }

    public function load($file)
    {
        $this->excelObject = PHPExcel_IOFactory::load($file);
    }

    public function getCoord($x, $y = null)
    {
        return PHPExcel_Cell::stringFromColumnIndex($x) . ($y !== null ? $y + 1 : '');
    }

    public function outputFromArray($header, $data, $title = 'Kryptos', $fileName = null)
    {
        $objPHPExcel = $this->createEmptyDocument();
        $objPHPExcel->getProperties()->setTitle($title);

        $sheet = $objPHPExcel->getActiveSheet();

        $this->insertTableHeader($sheet, $header);
        $this->insertSquareData($sheet, $data, 0, 1);

        $this->outputAsAttachment($objPHPExcel, $fileName);
    }

    public function outputAsAttachment($objPHPExcel, $fileName = 'export.xls')
    {
        header('Content-type: application/vnd.ms-excel');
        header(sprintf('Content-Disposition: attachment; filename="%s"', $fileName));

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

        exit;
    }

    /**
     * @param PHPExcel_Worksheet $sheet
     * @param array $data
     * @param int $startX
     * @param int $startY
     */
    public function insertTableHeader($sheet, $data, $startX = 0, $currentY = 0)
    {
        $currentX = $startX;

        foreach ($data as $value) {
            $cell = $this->getCoord($currentX, $currentY);

            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $sheet->getColumnDimension($this->getCoord($currentX))
                ->setAutoSize(true);

            $currentX++;
        }
    }

    /**
     * @param PHPExcel_Worksheet $sheet
     * @param array $data
     * @param int $startX
     * @param int $startY
     */
    public function insertSquareData($sheet, $data, $startX = 0, $startY = 0)
    {
        $currentY = $startY;

        foreach ($data as $columns) {
            $currentX = $startX;

            foreach (array_values($columns) as $value) {
                $sheet->SetCellValue($this->getCoord($currentX, $currentY), $value);

                $currentX++;
            }

            $currentY++;
        }
    }

    /**
     * @param PHPExcel_Worksheet $sheet
     * @param string $header
     * @param int $column
     * @param int $end
     * @param int $start
     * @param int $space
     */
    public function insertSummary($sheet, $header, $column, $end, $start = 0, $space = 2)
    {
        $startCell   = $this->getCoord($column, $start);
        $endCell     = $this->getCoord($column, $end);
        $headerCell  = $this->getCoord($column, $end + $space);
        $summaryCell = $this->getCoord($column, $end + $space + 1);

        $formula = sprintf('=SUM(%s:%s)', $startCell, $endCell);

        $sheet->setCellValue($headerCell, $header);
        $sheet->getStyle($headerCell)->getFont()->setBold(true);
        $sheet->getStyle($headerCell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->SetCellValue($summaryCell, $formula);
    }

    /** @return PHPExcel */
    public function createEmptyDocument()
    {
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Kryptos24");
        $objPHPExcel->getProperties()->setDescription("Dokument utworzony za pomocą programu Kryptos24. kryptos.co");
        $objPHPExcel->setActiveSheetIndex(0);

        return $objPHPExcel;
    }
}