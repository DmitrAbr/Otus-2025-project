<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if(!CModule::IncludeModule("iblock"))
{
	return;
}

$arComponentParameters = Array(
	"GROUPS" => array(
		"LIST"=>array(
			"NAME" => GetMessage("NAME_SECTION_PARAMETERS_GRID"),
			"SORT"=>'300'
		)	
	),
	"PARAMETERS" => array(
		"SHOW_CHECKBOXES" => array(
			"PARENT" => "LIST",
			"NAME" => GetMessage("NAME_PARAMS_SHOW_BUTTON"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"
		),
		"NUM_PAGE" => array(
			"PARENT" => "LIST",
			"NAME" => GetMessage("NAME_PARAMS_NUM_PAGES"),
			"TYPE" => "INT",
			"DEFAULT" => 20
		)
	)
);