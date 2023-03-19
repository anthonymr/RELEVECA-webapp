<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

include "config.php";
include "functions.php";

session_start();

$data = json_decode(file_get_contents("php://input"));
$request = $data->request;
$user = $_SESSION["id"];
$corporation = $_SESSION["corporation"];
$corporationName = getCorporationName($link, $corporation);

switch ($request) {
    case "getModules":
        genericRequest($link, "CALL sp_get_modules($user, $corporation)");
        break;
    case "getSubmodules":
        genericRequest($link, "CALL sp_get_submodules($user, $corporation, $data->module)");
        break;
    case "getAllSubmodules":
        genericRequest($link, "CALL sp_get_all_submodules($user, $corporation)");
        break;
    case "getItems":
        $from = $data->from;
        $display = $data->display;
        $filter = $data->filter;
        genericRequest($link, "SELECT * FROM $corporationName.items WHERE stock > 0 
            AND ( 
                name like '%$filter%'
                OR code like '%$filter%'
                OR model like '%$filter%'
            )
            ORDER BY id LIMIT $from, $display");
        break;
    case "getItemsCount":
        $filter = $data->filter;
        genericRequest($link, "SELECT count(*) AS count FROM $corporationName.items WHERE stock > 0 
            AND ( 
                name like '%$filter%'
                OR code like '%$filter%'
                OR model like '%$filter%'
            )
        ");
        break;
    case "getClients":
        $from = $data->from;
        $display = $data->display;
        $filter = $data->filter;
        genericRequest($link, "SELECT * FROM $corporationName.clients WHERE inactive = 0 
            AND ( 
                name like '%$filter%'
                OR code like '%$filter%'
            )
            ORDER BY id LIMIT $from, $display");
        break;
    case "getClientsCount":
        $filter = $data->filter;
        genericRequest($link, "SELECT count(*) AS count FROM $corporationName.clients WHERE inactive = 0 
            AND ( 
                name like '%$filter%'
                OR code like '%$filter%'
            )
        ");
        break;
    case "getClientsForInput":
        genericRequest($link, "SELECT id, name, code, taxpayer FROM $corporationName.clients WHERE inactive = 0 ORDER BY name");
        break;
    case "storePicture":
        $newImage = '../assets/images/items/'.$data->name;
        echo $newImage;
        storePicture($newImage , $data->pic);
        if ($data->firstPicture){
            genericUpdate($link, "UPDATE $corporationName.items SET `images` = '$data->name' WHERE (`id` = '$data->id');");
        } else {
            genericUpdate($link, "UPDATE $corporationName.items SET `images` = CONCAT_WS(',',`images`, '$data->name') WHERE (`id` = '$data->id');");
        }
        
        break;
    case "getCurrencies":
        genericRequest($link, "SELECT * FROM $corporationName.currency");
        break;
    case "getConditions":
        genericRequest($link, "SELECT * FROM $corporationName.conditions");
        break;
    case "setQuote":
        $sql = "INSERT INTO $corporationName.quotes 
            (`client_id`, `sub_total`, `taxes`, `total`, `condition`, `rate`, `currency`) 
            VALUES 
            ('$data->id', '$data->sub_total', '$data->taxes', '$data->total', '$data->condition', '$data->rate', '$data->currency');
        ";

        $id = 0;
        $details = $data->details;

        if (mysqli_query($link, $sql)) {
            $id = mysqli_insert_id($link);

            foreach($details as $i){
                genericUpdate($link, "INSERT INTO $corporationName.`quotes_details` 
                    (`quote_id`, `item_id`, `qty`, `unit_price`, `unit`, `total_price`, `currency`, `rate`) 
                    VALUES 
                    ('$id', '$i->item_id', '$i->qty', '$i->unit_price', '$i->unit', '$i->total', '$data->currency', '$data->rate');");
                echo 200;
            }
        }
        break;
    case "getQuotes":
        $from = $data->from;
        $display = $data->display;
        $filter = $data->filter;
        genericRequest($link, "SELECT *, quotes.id AS quote_id FROM $corporationName.quotes
            LEFT JOIN $corporationName.clients ON clients.id = quotes.client_id
            LEFT JOIN $corporationName.conditions ON conditions.code = quotes.condition
            LEFT JOIN $corporationName.currency ON currency.code = quotes.currency
            WHERE clients.name like '%$filter%'
            ORDER BY quotes.creation_date DESC LIMIT $from, $display
        ");
        break;
    case "getQuotesCount":
        $filter = $data->filter;
        genericRequest($link, "SELECT count(*) AS count FROM $corporationName.quotes
            LEFT JOIN $corporationName.clients ON clients.id = quotes.client_id
            WHERE clients.name like '%$filter%'
        ");
        break;
}
