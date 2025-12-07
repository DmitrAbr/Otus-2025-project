<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("NAME_COMPONENT"),
	"DESCRIPTION" => GetMessage("DESCRIPTION_COMPONENT"),
	"SORT" => 1,
	"PATH" => array(
		"ID" => "Otus",
		"CHILD" => array(
			"ID" => GetMessage("NAME_GROUP"),
		)
	),
);
?>