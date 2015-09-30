<?php

Route::get('/', 'Home\HomeController@index');
Route::get('/pdf', 'Home\ReportController@pdf');
Route::get('/report', 'Home\ReportController@index');
Route::get('/sync', 'Home\HomeController@syncSurveyMonkey');
Route::get('/dev', 'Home\HomeController@dev');
Route::get('/csv', 'Home\HomeController@csv');
Route::get('/hash', 'Home\HomeController@generateFamilyHashes');
Route::get('/family/{family_hash_id}', 'Home\FamilyController@show');

//\DB::listen(function($sql, $bindings, $time) {
//    r($sql);
//    r($bindings);
//    r($time);
//});