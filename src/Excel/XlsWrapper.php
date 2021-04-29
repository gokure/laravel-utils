<?php

namespace Gokure\Utils\Excel;

use Illuminate\Support\Facades\Response;
use SplFileInfo;
use Vtiful\Kernel\Excel;

/**
 * Class XlsWrapper
 *
 * @package Gokure\Utils\Excel
 * @method XlsWrapper fileName(string $fileName, string $sheetName = 'Sheet1') File Name
 * @method XlsWrapper constMemory(string $fileName, string $sheetName = 'Sheet1') Const memory model
 * @method XlsWrapper addSheet(string $sheetName) Add a new worksheet to a workbook.
 * @method XlsWrapper checkoutSheet(string $sheetName) Checkout worksheet
 * @method XlsWrapper activateSheet(string $sheetName) Set activate sheet
 * @method XlsWrapper header(array $header) Insert data on the first line of the worksheet
 * @method XlsWrapper data(array $data) Insert data on the worksheet
 * @method string output() Generate file
 * @method resource getHandle() Get file resource
 * @method XlsWrapper autoFilter(string $range) Auto filter on the worksheet
 * @method XlsWrapper insertText(int $row, int $column, $data, string $format = null, $formatHandle = null) Insert data on the cell
 * @method XlsWrapper insertDate(int $row, int $column, int $timestamp, string $format = null, $formatHandle = null) Insert date on the cell
 * @method XlsWrapper insertChart(int $row, int $column, $chartResource) Insert chart on the cell
 * @method XlsWrapper insertUrl(int $row, int $column, string $url, $formatHandle = null) Insert url on the cell
 * @method XlsWrapper insertImage(int $row, int $column, string $imagePath, float $width = 1, float $height = 1) Insert image on the cell
 * @method XlsWrapper insertFormula(int $row, int $column, string $formula, $formatHandle = null) Insert Formula on the cell
 * @method XlsWrapper insertComment(int $row, int $column, string $comment) Insert comment on the cell
 * @method XlsWrapper showComment() Show comment on the sheet
 * @method XlsWrapper mergeCells(string $range, string $data, $formatHandle = null) Merge cells
 * @method XlsWrapper setColumn(string $range, float $cellWidth, $formatHandle = null) Set column cells width or format
 * @method XlsWrapper setRow(string $range, float $cellHeight, $formatHandle = null) Set row cells height or format
 * @method XlsWrapper defaultFormat($formatHandle) Default format
 * @method XlsWrapper openFile(string $fileName) Open xlsx file
 * @method XlsWrapper openSheet(string $sheetName = null, int $skipFlag = 0x00) Open sheet
 * @method bool putCSV(resource $handler, string $delimiter = ',', string $enclosure = '"', string $escape = '\\') File to csv
 * @method bool putCSVCallback(callable $callback, resource $handler, string $delimiter = ',', string $enclosure = '"', string $escape = '\\') File to csv
 * @method array sheetList() Sheet list
 * @method XlsWrapper setType(array $types) Set row cell data type
 * @method XlsWrapper setSkipRows(int $rows) Set skip rows
 * @method array getSheetData() Read values from the sheet
 * @method array nextRow() Read values from the sheet
 * @method void nextCellCallback(callable $callback, string $sheetName = null) Next Cell In Callback
 * @method XlsWrapper freezePanes(int $row, int $column) Freeze panes
 * @method XlsWrapper gridline(int $option = Excel::GRIDLINES_HIDE_ALL) Gridline
 * @method XlsWrapper zoom(int $scale = 100) Worksheet zoom
 * @method static int columnIndexFromString(string $cellCoordinates) Column index from string
 * @method static string stringFromColumnIndex(int $cellCoordinates) String from column index
 * @method static int timestampFromDateDouble(float $date) Timestamp from double date
 */
class XlsWrapper
{
    const TYPE_STRING = Excel::TYPE_STRING;
    const TYPE_INT = Excel::TYPE_INT;
    const TYPE_DOUBLE = Excel::TYPE_DOUBLE;
    const TYPE_TIMESTAMP = Excel::TYPE_TIMESTAMP;

    const SKIP_NONE = Excel::SKIP_NONE;
    const SKIP_EMPTY_ROW = Excel::SKIP_EMPTY_ROW;
    const SKIP_EMPTY_CELLS = Excel::SKIP_EMPTY_CELLS;
    const SKIP_EMPTY_VALUE = Excel::SKIP_EMPTY_VALUE;

    const GRIDLINES_HIDE_ALL = Excel::GRIDLINES_HIDE_ALL;
    const GRIDLINES_SHOW_SCREEN = Excel::GRIDLINES_SHOW_SCREEN;
    const GRIDLINES_SHOW_PRINT = Excel::GRIDLINES_SHOW_PRINT;
    const GRIDLINES_SHOW_ALL = Excel::GRIDLINES_SHOW_ALL;

    /**
     * @var Excel
     */
    protected $excel;

    /**
     * Create excel file
     *
     * @param SplFileInfo|string $file
     * @param array $config
     *  - sheet_name: `string` Sheet name, default is `Sheet1`
     *  - const_memory: `bool` Create file with const memory, default `true`
     * @return static
     */
    public static function create($file = null, array $config = [])
    {
        if ($file === null) {
            $file = tempnam(sys_get_temp_dir(), 'excel_');
        } elseif ($file instanceof SplFileInfo) {
            $file = $file->getPathname();
        }

        $parts = pathinfo($file);

        $sheetName = $config['sheet_name'] ?? 'Sheet1';
        $constMemory = $config['const_memory'] ?? true;
        unset($config['sheet_name'], $config['const_memory']);

        $config['path'] = $parts['dirname'];

        $excel = new Excel($config);

        $xlsWrapper = new static($excel);
        if ($constMemory) {
            $xlsWrapper->constMemory($parts['basename'], $sheetName);
        } else {
            $xlsWrapper->fileName($parts['basename'], $sheetName);
        }

        return $xlsWrapper;
    }

    /**
     * Open excel file.
     *
     * @param SplFileInfo|string $file
     * @param array $config
     * @return static
     */
    public static function open($file, array $config = []): XlsWrapper
    {
        if ($file instanceof SplFileInfo) {
            $file = $file->getPathname();
        }

        $parts = pathinfo($file);

        $config['path'] = $parts['dirname'];

        $excel = new Excel($config);

        $xlsWrapper = new static($excel);
        $xlsWrapper->openFile($parts['basename']);

        return $xlsWrapper;
    }

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    /**
     * @return Excel
     */
    public function getExcel(): Excel
    {
        return $this->excel;
    }

    /**
     * Open sheet by index
     *
     * @param int $index
     * @param int $skipFlag
     * @return XlsWrapper
     */
    public function openSheetByIndex(int $index = 0, $skipFlag = self::SKIP_NONE): XlsWrapper
    {
        $sheets = $this->excel->sheetList();

        return $this->openSheet($sheets[$index] ?? null, $skipFlag);
    }

    /**
     * Iterator rows with header as key.
     *
     * @param array $header
     * @param null $pad
     * @return \Generator
     */
    public function iterRowWithHeader($header = null, $pad = null): \Generator
    {
        if ($header === null) {
            // 获取表头
            $header = $this->nextRow();
        }

        if ($header !== null) {
            $keysCount = count($header);
            if ($header !== null) {
                $header = array_map('strtolower', $header); // 将下标转为小写字符
            }
            while (($row = $this->nextRow()) !== null) {
                $rowCount = count($row);
                if ($rowCount > $keysCount) {
                    $row = array_slice($row, 0, $keysCount);
                } elseif ($rowCount < $keysCount) {
                    $row = array_pad($row, $keysCount, $pad);
                }

                yield array_combine($header, $row);
            }
        }
    }

    /**
     * Download file
     *
     * @param null $name
     * @param array $headers
     * @param string $disposition
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($name = null, array $headers = [], $disposition = 'attachment')
    {
        return Response::download($this->output(), $name, $headers, $disposition);
    }

    public function __get($name)
    {
        return $this->excel->{$name};
    }

    public function __set($name, $value)
    {
        $this->excel->{$name} = $value;
    }

    public function __isset($name)
    {
        return isset($this->excel->{$name});
    }

    /**
     * 动态调用 Excel 实例方法，当返回值为 Excel 实例时会使用 XlsWrapper 进行重新包装，即返回当前对象的实例，否则返回实际结果
     *
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $result = $this->excel->{$method}(...$parameters);

        return $result instanceof Excel ? $this : $result;
    }

    public static function __callStatic($method, $parameters)
    {
        return Excel::{$method}(...$parameters);
    }
}
