<?php

error_reporting(E_ALL);
$vendorDirPath = realpath(__DIR__ . '/vendor');
if (file_exists($vendorDirPath . '/autoload.php')) {
    require $vendorDirPath . '/autoload.php';
} else {
    throw new Exception(
        sprintf(
            'Could not found autoload file.'
        )
    );
}

// OPENAPI
// https://petstore.swagger.io/v2/swagger.json
$file = file_get_contents('./data/input/swagger.json');

$doc = json_decode($file, true);

// Creating the new document.
$phpWord = new \PhpOffice\PhpWord\PhpWord;
$phpWord->addTitleStyle(null, ['size' => 22, 'bold' => true]);
$phpWord->addTitleStyle(1, ['size' => 20, 'color' => '333333', 'bold' => true]);
$phpWord->addTitleStyle(2, ['size' => 16, 'color' => '666666']);
$phpWord->addTitleStyle(3, ['size' => 14, 'italic' => true]);
$phpWord->addTitleStyle(4, ['size' => 12]);
\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

$header = ['size' => 16, 'bold' => true];

// Fancy Table
$fancyTableStyleName = 'Fancy Table';
$fancyTableStyle = [
    'borderSize' => 6,
    'borderColor' => '006699',
    'cellMargin' => 80,
    'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
    'cellSpacing' => 0
];
$fancyTableFirstRowStyle = [
    'borderBottomSize' => 18,
    'borderBottomColor' => '0000FF',
    'bgColor' => '66BBFF'
];
$fancyTableCellStyle = [
    'valign' => 'center'
];
$fancyTableCellBtlrStyle = [
    'valign' => 'center',
    'textDirection' => \PhpOffice\PhpWord\Style\Cell::TEXT_DIR_BTLR
];
$fancyTableFontStyle = [
    'bold' => true
];
$phpWord->addTableStyle($fancyTableStyleName, $fancyTableStyle, $fancyTableFirstRowStyle);

// 封面
$section = $phpWord->addSection();
$section->addTitle($doc['info']['title'], 0);
$section->addTextBreak(2);
$section->addTitle($doc['info']['description'], 2);
$section->addTextBreak();
$section->addText('版本 ' . $doc['info']['version']);

// 修订历史
$section = $phpWord->addSection();
$section->addText('修订历史');
$section->addTextBreak();
$table = $section->addTable($fancyTableStyleName);
$table->addRow();
$table->addCell(1110, $fancyTableCellStyle)->addText('版本号', $fancyTableFontStyle);
$table->addCell(1610, $fancyTableCellStyle)->addText('修订者', $fancyTableFontStyle);
$table->addCell(4110, $fancyTableCellStyle)->addText('修订内容概要', $fancyTableFontStyle);
$table->addCell(1410, $fancyTableCellStyle)->addText('发布日期', $fancyTableFontStyle);
$table->addRow();
$table->addCell(1110, $fancyTableCellStyle)->addText('1.0.0');
$table->addCell(1610, $fancyTableCellStyle)->addText('飞飞鸟');
$table->addCell(4110, $fancyTableCellStyle)->addText('API文档 v1.0.0 完成');
$table->addCell(1410, $fancyTableCellStyle)->addText('2020-10-28');

// 文档主体
$section = $phpWord->addSection();
$no = 0;
foreach ($doc['paths'] as $path => $api) {
    $method = isset($api['get']) ? 'get' : (isset($api['post']) ? 'post' : (isset($api['put']) ? 'put' : (isset($api['delete']) ? 'delete' : '')));
    if (!$method) {
        continue;
    }

    $no++;
    $obj = $api[$method];
    $section->addTextBreak(2);
    $section->addTitle($no . '. ' . $obj['summary'], 3);
    $section->addTextBreak();
    $section->addTitle('API：' . $path, 4);
    $section->addTitle('请求方式：' . $method, 4);
    $section->addTitle('所属服务：' . $obj['tags'][0] ?? '', 4);
    $section->addTitle('描述：' . $obj['description'], 4);

    // 1.1 请求参数
    $section->addTextBreak();
    $parameters = $obj['parameters'] ?? [];
    $section->addTitle($no . '.1 请求参数', 4);
    $section->addTextBreak();
    if ($parameters) {
        $table = $section->addTable($fancyTableStyleName);
        $table->addRow();
        $table->addCell(1500, $fancyTableCellStyle)->addText('名称', $fancyTableFontStyle);
        $table->addCell(1000, $fancyTableCellStyle)->addText('位置', $fancyTableFontStyle);
        $table->addCell(3200, $fancyTableCellStyle)->addText('描述', $fancyTableFontStyle);
        $table->addCell(900, $fancyTableCellStyle)->addText('必填', $fancyTableFontStyle);
        $table->addCell(1200, $fancyTableCellStyle)->addText('类型', $fancyTableFontStyle);
        $table->addCell(1300, $fancyTableCellStyle)->addText('例子', $fancyTableFontStyle);
        foreach ($parameters as $row) {
            $table->addRow();
            $table->addCell(1500, $fancyTableCellStyle)->addText($row['name'] ?? '');
            $table->addCell(1000, $fancyTableCellStyle)->addText($row['in'] ?? '');
            $table->addCell(3200, $fancyTableCellStyle)->addText($row['description'] ?? '');
            $table->addCell(900, $fancyTableCellStyle)->addText(($row['required'] ?? true) ? '是' : '否');
            $table->addCell(1200, $fancyTableCellStyle)->addText($row['schema']['type'] ?? '');
            $table->addCell(1300, $fancyTableCellStyle)->addText($row['schema']['example'] ?? '');
        }
    } else {
        $section->addText('无');
    }

    // 1.2 请求体
    $section->addTextBreak();
    $schema = $obj['requestBody']['content']['application/json']['schema'] ?? [];
    $section->addTitle($no . '.2 请求体', 4);
    if (isset($schema['type'])) {
        $section->addTextBreak();
        $section->addText('类型：' . $schema['type']);
    }

    $section->addTextBreak();
    $section->addTitle($no . '.2.1 请求体参数', 4);
    $section->addTextBreak();
    if (isset($schema['properties'])) {
        $table = $section->addTable($fancyTableStyleName);
        $table->addRow();
        $table->addCell(1720, $fancyTableCellStyle)->addText('属性', $fancyTableFontStyle);
        $table->addCell(3420, $fancyTableCellStyle)->addText('描述', $fancyTableFontStyle);
        $table->addCell(1320, $fancyTableCellStyle)->addText('类型', $fancyTableFontStyle);
        $table->addCell(1220, $fancyTableCellStyle)->addText('可以为空', $fancyTableFontStyle);
        foreach ($schema['properties'] as $key => $row) {
            $table->addRow();
            $table->addCell(1720, $fancyTableCellStyle)->addText($key);
            $table->addCell(3420, $fancyTableCellStyle)->addText($row['description'] ?? '');
            $table->addCell(1750, $fancyTableCellStyle)->addText($row['type'] ?? '');
            $table->addCell(1220, $fancyTableCellStyle)->addText(($row['nullable'] ?? false) ? '是' : '否');
        }
    } else {
        $section->addText('无');
    }

    $section->addTextBreak();
    $section->addTitle($no . '.2.2 请求体样例', 4);
    $section->addTextBreak();
    if (isset($schema['example'])) {
        $section->addText(\json_encode($schema['example'], JSON_UNESCAPED_UNICODE));
    } else {
        $section->addText('无');
    }

    // 1.3 成功响应
    $section->addTextBreak();
    $section->addTitle($no . '.3 成功返回', 4);
    if (isset($schema['type'])) {
        $section->addTextBreak();
        $section->addText('类型：' . $schema['type']);
    }

    $section->addTextBreak();
    $section->addTitle($no . '.3.1 成功返回参数', 4);
    $section->addTextBreak();
    $schema = $obj['responses']['200']['content']['success']['schema'] ?? [];
    if ($schema) {
        if (isset($schema['properties'])) {
            $table = $section->addTable($fancyTableStyleName);
            $table->addRow();
            $table->addCell(1720, $fancyTableCellStyle)->addText('属性', $fancyTableFontStyle);
            $table->addCell(3420, $fancyTableCellStyle)->addText('描述', $fancyTableFontStyle);
            $table->addCell(1320, $fancyTableCellStyle)->addText('类型', $fancyTableFontStyle);
            $table->addCell(1220, $fancyTableCellStyle)->addText('可以为空', $fancyTableFontStyle);
            foreach ($schema['properties'] as $key => $row) {
                $table->addRow();
                $table->addCell(1720, $fancyTableCellStyle)->addText($key);
                $table->addCell(3420, $fancyTableCellStyle)->addText($row['description'] ?? '');
                $table->addCell(1320, $fancyTableCellStyle)->addText($row['type'] ?? '');
                $table->addCell(1220, $fancyTableCellStyle)->addText(($row['nullable'] ?? false) ? '是' : '否');
            }
        }
    } else {
        $section->addText('无');
    }

    $section->addTextBreak();
    $section->addTitle($no . '.3.2 成功返回样例', 4);
    $section->addTextBreak();
    if (isset($schema['example'])) {
        $section->addText(\json_encode($schema['example'], JSON_UNESCAPED_UNICODE));
    } else {
        $section->addText('无');
    }

    // 1.4 失败响应
    $section->addTextBreak();
    $section->addTitle($no . '.4 失败返回', 4);
    if (isset($schema['type'])) {
        $section->addTextBreak();
        $section->addText('类型：' . $schema['type']);
    }

    $section->addTextBreak();
    $section->addTitle($no . '.4.1 失败返回参数', 4);
    $section->addTextBreak();
    $schema = $obj['responses']['200']['content']['fail']['schema'] ?? [];
    if ($schema) {
        if (isset($schema['properties'])) {
            $table = $section->addTable($fancyTableStyleName);
            $table->addRow();
            $table->addCell(1720, $fancyTableCellStyle)->addText('属性', $fancyTableFontStyle);
            $table->addCell(3420, $fancyTableCellStyle)->addText('描述', $fancyTableFontStyle);
            $table->addCell(1320, $fancyTableCellStyle)->addText('类型', $fancyTableFontStyle);
            $table->addCell(1220, $fancyTableCellStyle)->addText('可以为空', $fancyTableFontStyle);
            foreach ($schema['properties'] as $key => $row) {
                $table->addRow();
                $table->addCell(1720, $fancyTableCellStyle)->addText($key);
                $table->addCell(3420, $fancyTableCellStyle)->addText($row['description'] ?? '');
                $table->addCell(1320, $fancyTableCellStyle)->addText($row['type'] ?? '');
                $table->addCell(1220, $fancyTableCellStyle)->addText(($row['nullable'] ?? false) ? '是' : '否');
            }
        }
    } else {
        $section->addText('无');
    }

    $section->addTextBreak();
    $section->addTitle($no . '.4.2 失败返回样例', 4);
    $section->addTextBreak();
    if (isset($schema['example'])) {
        $section->addText(\json_encode($schema['example'], JSON_UNESCAPED_UNICODE));
    } else {
        $section->addText('无');
    }

    unset($doc['paths'][$path]);
}

// Saving the document as OOXML file.
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('data/output/result.doc');

echo 'OK';
