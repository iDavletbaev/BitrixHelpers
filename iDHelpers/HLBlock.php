<?php
namespace iDHelpers;

use \Bitrix\Main\Loader,
    \Bitrix\Main\Application,
    \Bitrix\Highloadblock\HighloadBlockTable,
    \Bitrix\Main\Entity;

class HLBlock {

    public static function getList($hlBlockName = '', $arParams = array(), $keyField = false)
    {
        $result = false;

        if (Loader::includeModule('highloadblock')) {

            $hlBlockEntity = \Bitrix\Highloadblock\HighloadBlockTable::getList(['filter' => ['NAME' => $hlBlockName] ])->fetch();

            if ($keyField && ! in_array($keyField, $arParams['select'])) {
                $arParams['select'][] = $keyField;
            }

            if ($hlBlockEntity) {
                $hlBlock = HighloadBlockTable::compileEntity($hlBlockEntity)->getDataClass();

                if ($rs = $hlBlock::getList($arParams)) {
                    $arList = array();
                    if ($keyField) {
                        while ($record = $rs->fetch()) {
                            $arList[ $record[$keyField] ] = $record;
                        }
                    } else {
                        while ($record = $rs->fetch()) {
                            $arList[] = $record;
                        }
                    }
                    $result = $arList;
                }
            }
        }

        return $result;
    }

    /*
     * возвращает список полей со значениями enum (тип список) в том числе
     */
    public static function getFields($hlBlockName = '')
    {
        if (! $hlBlockName) {
            return false;
        }

        $db = Application::getConnection();
        $dbHelper = $db->getSqlHelper();

        $hlb = $db->query("
            SELECT
              *
            FROM `b_hlblock_entity`
            WHERE `NAME` = '" . $dbHelper->forSql($hlBlockName) . "';
        ")->fetch();

        $hlbFields = array();
        $hlbEnumerationFields = array();
        if ($hlb && $rsHlbFields = $db->query("
                SELECT
                  *
                FROM `b_user_field`
                WHERE `ENTITY_ID` = 'HLBLOCK_" . intval($hlb['ID']) . "'
            ")
        ) {
            while ($field = $rsHlbFields->fetch()) {
                $hlbFields['by_id'][ $field['ID'] ] = $field;
                $hlbFields['by_code'][ $field['FIELD_NAME'] ] =& $hlbFields['by_id'][ $field['ID'] ];

                if ($field['USER_TYPE_ID'] == 'enumeration') {
                    $hlbEnumerationFields[ $field['ID'] ] = $field['FIELD_NAME'];
                }
            }
        }

        if ($hlbEnumerationFields && $rsEnums = $db->query("
                SELECT
                  `ID`,
                  `USER_FIELD_ID`,
                  `VALUE`,
                  `XML_ID`
                FROM `b_user_field_enum`
                WHERE `USER_FIELD_ID` in ('" . implode("','", array_keys($hlbEnumerationFields)) . "')
                ORDER BY SORT
            ")
        ) {
            while ($enum = $rsEnums->fetch()) {
                $userFieldId = $enum['USER_FIELD_ID'];
                $hlbFields['by_id'][ $userFieldId ]['ENUMS'][ $enum['ID'] ] = $enum;
            }
        }

        if ($hlbFields) {
            return $hlbFields;
        }

        return false;
    }

    public static function add($hlBlockName = '', $arData = array())
    {
        $result = false;

        # поиск дубля
        $item = array_pop(self::getList($hlBlockName, array('filter' => $arData, 'select' => array('ID'))));

        if (! $item) {
            if (Loader::includeModule('highloadblock')) {

                $hlBlockEntity = \Bitrix\Highloadblock\HighloadBlockTable::getList(['filter' => ['NAME' => $hlBlockName] ])->fetch();


                if ($hlBlockEntity) {
                    $hlBlock = HighloadBlockTable::compileEntity($hlBlockEntity)->getDataClass();

                    if ($rs = $hlBlock::add($arData)) {
                        $result = $rs->getId();
                    }
                }
            }
        } else {
            $result = $item['ID'];
        }

        return $result;
    }

    public static function removeItem($hlBlockName = '', $id = '')
    {
        $result = false;

        if ($id) {

            $hlBlockEntity = \Bitrix\Highloadblock\HighloadBlockTable::getList(['filter' => ['NAME' => $hlBlockName] ])->fetch();

            $hlbl = $hlBlockEntity['ID'];
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlbl)->fetch();

            $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $entity_data_class::Delete($id);

            $result = $id;
        }

        return $result;

    }

}

?>
