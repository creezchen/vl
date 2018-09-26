<?php
/**
 * Created by PhpStorm.
 * Date: 18.9.21
 * Time: 16:31
 */

namespace App\Http\Controllers\Frank\Models;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KmsVideo extends Model {
    protected $table = 'kms_video';
    protected $fillable = ['brand', 'item_group', 'item_model', 'type', 'descr', 'link', 'note'];

    public static function parseExcel($filepath) {

        $spreadsheet = IOFactory::load($filepath);

        // 所有 Sheet 都读取；
        // $sheets = $spreadsheet->getAllSheets();
        // foreach ($sheets as $sheet) { }

        $rows = [];

        // 根据指定名称读取，避免存在隐藏工作表，内容不对应的问题；
        $sheet = $spreadsheet->getSheetByName('Video List');

        for ($nul = 0, $i = 3; true; ++$i) {
            // 从指定行开始，一行一行读取；
            $row = $sheet->rangeToArray("A{$i}:G{$i}")[0];

            foreach ($row as &$cell) {
                $cell = trim($cell);
            }

            // 第F列是视频地址，不允许空；
            // 出现连续多个空行结束读取；
            if (empty($row[5])) {

                ++$nul;
                if ($nul > 5) break;

            } else {
                $nul = 0;
                $rows[] = array(
                    'brand' => $row[0],
                    'item_group' => $row[1],
                    'item_model' => $row[2],
                    'type' => $row[3],
                    'descr' => $row[4],
                    'link' => $row[5],
                    'note' => $row[6]
                );
            }

        }

        return $rows;
    }

    public static function import(Request $req, $types) {

        if ($req->has('link')) {

            $rows[] = $req->all();

        } elseif (empty($_FILES['excelfile']['size'])) {

            throw new \Exception('Please check the validity of the Excel file!');

        } elseif ($_FILES['excelfile']['error']) {

            throw new \Exception("Upload error: {$_FILES['excelfile']['error']}");

        } elseif ($_FILES['excelfile']['size'] > 5 * 1204 * 1204) {

            throw new \Exception('File exceeds 5M limit!');

        } else {

            $rows = self::parseExcel($_FILES['excelfile']['tmp_name']);

        }

        if (empty($rows)) {

            throw new \Exception('Import failed: Excel is empty or format error!');

        }

        $types = array_flip($types);

        foreach ($rows as $row) {
            if (!isset($types[$row['type']])) {
                $row['type'] = 'Others';
            }
            self::create($row);
        }

    }
}
