<?php
/**
 * DataTables 辅助
 */

namespace App\Http\Controllers\Frank\Traits;

use Illuminate\Http\Request;

trait DataTables {

    protected function dtWhere(Request $req) {

        $where = [];
        $search = $req->input('search');

        if (!empty($search['value'])) {
            if ('?' === $search['value'][0]) {
                parse_str(substr($search['value'], 1), $args);
                foreach ($args as $arg => $val) {
                    $arg = addslashes($arg);
                    $val = addslashes($val);
                    $where[] = "$arg='$val'";
                }
            } else {
                // todo 全文索引
            }
        }

        if (empty($where)) $where[] = 1;

        return implode(' AND ', $where);
    }

    protected function dtLimit(Request $req) {

        $start = (int)$req->input('start');
        $length = (int)$req->input('length');

        return "{$start},{$length}";
    }

    protected function dtOrderBy(Request $req) {

        $order = $req->input('order');
        $columns = $req->input('columns');

        $orderby = [];

        foreach ($order as $obj) {
            $index = $obj['column'];
            $field = $columns[$index]['name'];

            if (!preg_match('#^\w+$#', $field) || !preg_match('#^asc|desc$#i', $obj['dir'])) {
                throw new \Exception("INPUT ERROR: ORDER BY {$field} {$obj['dir']}", 101);
            }

            $orderby[] = "{$field} {$obj['dir']}";
        }

        return implode(',', $orderby);
    }
}
