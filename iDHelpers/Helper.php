<?php
namespace iDHelpers;

use \Bitrix\Main\Application,
    \Bitrix\Main\Config\Option,
    \Bitrix\Main\DB\Exception;

class Helper {

    /**
     *  Запись в лог.
     *
     *  @param string $fileName имя файла	log/aaa.html
     *  @param variant $data значение для журналирования
     *  @param int $line номер строки	__LINE__
     *  @param string $file путь к файлу	__FILE__
     *  @param bool $clear почистить ли файл
     *  @param string $headcolor выдилить цветом запись
     */
    public static function writeToLog($fileName = 'log1723.html', $data = null, $clear = false, $headcolor = "#EEEEEE")
    {
        $calledBy = debug_backtrace()[0];

        $line = $calledBy["line"];
        $file = $calledBy["file"];

        if (class_exists("CUser") && \CUser::IsAuthorized()) {
            $user = \CUser::GetFullName()."[".\CUser::GetID()."]";
        }

        $fileName = $fileName;

        if ($clear)
            unlink($_SERVER["DOCUMENT_ROOT"]."/".$fileName);

        $f_o = fopen($_SERVER["DOCUMENT_ROOT"]."/".$fileName,"a");

        fwrite($f_o, "<head><meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\"><div style='background-color:".$headcolor."; padding:5px; font-family:\"Tahoma\"; font-size:12px;'> <b>D</b>: ".date("Y-m-d\TH:i:s") . (isset($user) ? "&nbsp;&nbsp;&nbsp;<b>U</b>: ".$user : "") . (isset($line) ? "&nbsp;&nbsp;&nbsp;<b>L</b>: ".$line : "") . (isset($file) ? "&nbsp;&nbsp;&nbsp;<b>F</b>: ".$file : "") . "</div><pre>".print_r($data, true)."</pre>");

        fclose($f_o);
    }

    /**
     * @param null $data Переменная для вывода
     * @param bool $onlyForAdmin Выводить только для админов true/false
     */
    public static function print_r($data = null, $onlyForAdmin = true)
    {
        global $USER;

        $isAdmin = $USER->IsAdmin();

        if ($onlyForAdmin && $isAdmin || ! $onlyForAdmin) {
            $sender = debug_backtrace()[0];

            echo "<pre style='font-size: 12px'><span style='font-size: 12px; color: #AAA;'>" . $sender["file"] . " <span style='color: #666;'>[строка: " . $sender["line"] . "]</span></span><br>";
            print_r($data);
            echo "</pre>";
        }
    }

    /**
     *  Получение ID инфоблока по его коду
     *
     *  @param Array $arIblockCodes массив кодов
     */
    public static function getIblockIdByCodes($arIblockCodes) {
        $arIblocks = [];

        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            if ($rsIblocks = \CIBlock::GetList([], ['CODE' => $arIblockCodes])) {
                while ($iblock = $rsIblocks->Fetch()) {
                    $arIblocks[ $iblock['CODE'] ] = $iblock['ID'];
                }
            }
        }

        return $arIblocks;
    }


    /**
     * Получение справочника свойств инфоблока где ключём явлется код свойства
     *
     * @param null $iblockCode
     * @throws \Bitrix\Main\Db\SqlQueryException
     * @throws \Bitrix\Main\LoaderException
     */
    public static function getIBlockPropsReference($iblockCode = null)
    {
        $arRef = [];

        if (\Bitrix\Main\Loader::includeModule('iblock')) {

            $connection = Application::getConnection();
            $sqlHelper = $connection->getSqlHelper();

            if ($rsRef = $connection
                ->query("
                        SELECT
                          `bip`.`ID`,
                          `bip`.`CODE`,
                          `bip`.`NAME`,
                          `bip`.`PROPERTY_TYPE`,
                          `bipe`.`ID` `ENUM_ID`,
                          `bipe`.`XML_ID` `ENUM_XML_ID`,
                          `bipe`.`DEF` `ENUM_DEFAULT`,
                          `bipe`.`VALUE` `ENUM_VALUE`
                        FROM `b_iblock` `bi`
                          INNER JOIN `b_iblock_property` `bip`
                            ON `bi`.`ID` = `bip`.`IBLOCK_ID`
                          LEFT JOIN `b_iblock_property_enum` `bipe`
                            ON `bipe`.`PROPERTY_ID` = `bip`.`ID`
                        WHERE `bi`.`CODE` = '" . $sqlHelper->forSql($iblockCode) . "';
                    ")
            ) {
                while ($item = $rsRef->fetch()) {
                    $arRef[$item['CODE']]['ID'] = $item['ID'];
                    $arRef[$item['CODE']]['CODE'] = $item['CODE'];
                    $arRef[$item['CODE']]['NAME'] = $item['NAME'];
                    $arRef[$item['CODE']]['PROPERTY_TYPE'] = $item['PROPERTY_TYPE'];

                    if ($enumId = $item['ENUM_XML_ID'] ? $item['ENUM_XML_ID'] : $item['ENUM_ID']) {
                        $arRef[$item['CODE']]['ENUMS'][ $enumId ]['ID'] = $item['ENUM_ID'];
                        $arRef[$item['CODE']]['ENUMS'][ $enumId ]['XML_ID'] = $item['ENUM_XML_ID'];
                        $arRef[$item['CODE']]['ENUMS'][ $enumId ]['DEFAULT'] = $item['ENUM_DEFAULT'];
                        $arRef[$item['CODE']]['ENUMS'][ $enumId ]['VALUE'] = $item['ENUM_VALUE'];
                    }
                }
            }
        }

        return $arRef;
    }

    /**
     * Вывод исключение на экран и другие обработки
     */
    public static function exceptionHandler($e)
    {
        global $USER;

        echo '<div class="error-alert">Ошибка.<br>';
        if ($USER->IsAdmin()) {
            echo '<br>' . __FILE__ . ':' . __LINE__ . '<br><br>';
            echo $e->getMessage();
        }
        echo '</div>';
    }

    /**
     * получение протокола преедачи гипертекста
     */
    public static function getProtocol()
    {
        if (\CMain::IsHTTPS()) {
            return 'https';
        }

        return 'http';
    }

    /**
     * получение адреса сайта из настроек главного модуля
     */
    public static function getSiteURL()
    {
        if (defined('SITE_SERVER_NAME')) {
            return SITE_SERVER_NAME;
        }

        if ($mainModuleServerName = Option::get('main', 'server_name', '')) {
            return $mainModuleServerName;
        }

        return '';
    }
}



?>