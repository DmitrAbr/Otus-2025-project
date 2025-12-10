<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("sibcem.processes"))
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
		
	)
);