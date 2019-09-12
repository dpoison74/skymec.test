<?
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

class skymecTest
{

	/**
	 * Преобразование типа скидки
	 *
	 * @param $arDiscount array Массив типа скидки
	 * @return bool|string Тип скидки или false
	 */
	private static function getDiscountType($arDiscount)
	{
		switch ($arDiscount['Type'])
		{
			case "value":
				return "CurEach";
			case "proc":
				return "Perc";
		}

		return false;
	}

	/**
	 * @param $arExternalDiscount array
	 * @return bool|int Идентификатор созданного правила или false
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function convertDiscount($arExternalDiscount)
	{
		//Определим массив типа скидки
		$arDiscountType = array(
			"Type" => "Discount",
			"Value" => $arExternalDiscount["Discount"]["Value"],
			"Unit" => skymecTest::getDiscountType($arExternalDiscount["Discount"]),
			"Max" => 0,
			"All" => "AND",
			"True" => "True",
		);

		//Определим массив продукта для применения скидки
		$arProduct[] = array(
			"CLASS_ID" => "CondIBXmlID",
			"DATA" => array(
				"logic" => "Equal",
				"value" => $arExternalDiscount["Guid"]
			)
		);

		//Определим массив действий
		$arBasketActions = array(
			"CLASS_ID" => "CondGroup",
			"DATA" => array(
				"All" => "AND"
			),
			"CHILDREN" => array(
				array(
					"CLASS_ID" => "ActSaleBsktGrp",
					"DATA" => $arDiscountType, //Какую скидку
					"CHILDREN" => $arProduct //На какией продукты
				)
			)
		);

		//Определим массив доп условий
		$arBasketConditions = array(
			"CLASS_ID" => "CondGroup",
			"DATA" => array(
				"All" => "AND",
				"True" => "True"
			)
		);

		//Обойдем условия для начисления скидки
		foreach ($arExternalDiscount["Target"] as $arTarget):
			foreach ($arTarget as $arTargetValue):

				//Определим массив продуктов-условий применения скидки
				$arNeedleProduct = array(
					array(
						"CLASS_ID" => "CondIBCode",
						"DATA" => array("logic" => "Equal", "value" => $arTargetValue["Guid"]),
					),
					array(
						"CLASS_ID" => "CondBsktFldQuantity",
						"DATA" => array("logic" => "EqGr", "value" => $arTargetValue["Quantity"])
					)
				);

				$arBasketConditions["CHILDREN"][] = array(
					"CLASS_ID" => "CondBsktProductGroup",
					"DATA" => array(
						"Found" => "Found",
						"All" => "AND"
					),
					"CHILDREN" => $arNeedleProduct
				);

			endforeach;
		endforeach;


		//Подулючим модуль "Интернет магазин"
		if (Loader::includeModule('sale')):

			//Определим параметры правила корзины
			$arSaleFields = array(
				"LID" => SITE_ID,
				"NAME" => $arExternalDiscount["Name"],
				"USER_GROUPS" => array(2), //Все пользователи, в том числе неавторизованные
				"ACTIVE" => "Y",
				"ACTIONS" => $arBasketActions,
				"CONDITIONS" => $arBasketConditions
			);

			return CSaleDiscount::Add($arSaleFields);
		endif;

		return false;

	}

}

/***********************************/
//Полученное описание скидки
$arExternalDiscount = array(
	"Guid" => "b577427d - c3fa - 11e9 - 80c6 - 00155da7d607",
	"Name" => "Набор аксессуаров DJI Osmo Pocket Expansion Kit(Part 13) и Экшн - камера DJI Osmo Pocket",
	"Active" => 1,
	"Period" => array
	(
		"Start" => "",
		"End" => "",
	),
	"Partners" => array(),
	"Promocodes" => array(),
	"Target" => array
	(
		Array
		(
			Array
			(
				"Guid" => "9f8ff181 - f353 - 11e8 - 80be - 00155da7d607",
				"Name" => "Экшн - камера DJI Osmo Pocket",
				"Quantity" => 1,
				"NoSetDiscount" => 1
			),
			Array
			(
				"Guid" => "7cf77d2c - f4a9 - 11e8 - 80be - 00155da7d607",
				"Name" => "Набор аксессуаров DJI Osmo Pocket Expansion Kit(Part 13)",
				"Quantity" => 1,
				"NoSetDiscount" => ""
			)
		)
	),
	"Gift" => Array(),
	"Discount" => Array
	(
		"Type" => "value",
		"Value" => 2400
	)
);

//Добавим правило по заданным параметрам
$ruleID = skymecTest::convertDiscount($arExternalDiscount);

if ($ruleID)
	echo "Добавлено правило #{$ruleID}";
else
	echo "При добавлении правила возникла ошибка";