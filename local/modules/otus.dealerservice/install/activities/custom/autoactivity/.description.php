<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPAA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPAA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "AutoActivity",
	"CATEGORY" => array(
		"ID" => "other",
	),
	"RETURN" => array(
		'CompanyId' => [
			'NAME' => GetMessage("TITLE_COMPANY_ID"),
			'TYPE' => 'int'
		]
        
	),
);