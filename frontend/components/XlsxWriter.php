<?php

namespace app\components;

/**
 * نوشتن فایل xlsx به صورت جریانی (سطر به سطر روی دیسک) با حافظه‌ی ثابت.
 *
 * برای خروجی‌های بزرگ به جای PhpSpreadsheet استفاده می‌شود که کل فایل را در حافظه نگه می‌دارد
 * (خطای Allowed memory size با چند هزار ردیف). همه‌ی متن‌ها inlineStr نوشته می‌شوند تا
 * اکسل صفر اولِ موبایل و کد ملی را حذف نکند. فقط به افزونه‌ی zip نیاز دارد.
 */
class XlsxWriter
{
    private $sheetPath;
    private $handle;
    private $row = 0;
    private $columnCount;

    /**
     * @param string[] $headers عنوان ستون‌ها (ردیف اول، پررنگ)
     * @param int[] $widths عرض ستون‌ها (اختیاری)
     */
    public function __construct(array $headers, array $widths = [])
    {
        $this->columnCount = count($headers);
        $this->sheetPath = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->handle = fopen($this->sheetPath, 'wb');
        $cols = '';
        foreach (array_values($headers) as $i => $header) {
            $width = isset($widths[$i]) ? (int) $widths[$i] : 18;
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $width . '" customWidth="1"/>';
        }
        fwrite($this->handle, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" rightToLeft="1"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>');
        $this->addRow($headers, true);
    }

    /**
     * @param array $cells رشته یا عدد (int/float). null خالی نوشته می‌شود.
     */
    public function addRow(array $cells, $bold = false)
    {
        $this->row++;
        $xml = '<row r="' . $this->row . '">';
        $i = 0;
        foreach ($cells as $value) {
            $ref = self::columnName($i++) . $this->row;
            $style = $bold ? ' s="1"' : '';
            if ($value === null || $value === '') {
                continue;
            } else if (is_int($value) || is_float($value)) {
                $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . self::escape($value) . '</t></is></c>';
            }
        }
        fwrite($this->handle, $xml . '</row>');
    }

    public function rowCount()
    {
        return max(0, $this->row - 1);
    }

    /**
     * فایل نهایی را می‌سازد.
     */
    public function save($path)
    {
        fwrite($this->handle, '</sheetData></worksheet>');
        fclose($this->handle);

        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($this->sheetPath);
            throw new \RuntimeException('Cannot create xlsx file');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Tahoma"/></font><font><b/><sz val="11"/><name val="Tahoma"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '</styleSheet>');
        $zip->addFile($this->sheetPath, 'xl/worksheets/sheet1.xml');
        $zip->close(); // فایل موقت sheet هنگام close خوانده می‌شود؛ بعد از آن حذف می‌شود
        @unlink($this->sheetPath);
    }

    public static function columnName($index)
    {
        $name = '';
        for ($n = $index; $n >= 0; $n = intdiv($n, 26) - 1)
            $name = chr(65 + $n % 26) . $name;
        return $name;
    }

    /**
     * متن امن برای XML: UTF-8 نامعتبر اصلاح و کاراکترهای کنترلی غیرمجاز حذف می‌شوند.
     */
    private static function escape($value)
    {
        $value = (string) $value;
        if (!mb_check_encoding($value, 'UTF-8'))
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value);
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
