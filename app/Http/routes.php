<?php

Route::get('/', 'Home\HomeController@index');
Route::get('/csv', 'Home\HomeController@csv');
Route::get('/hash', 'Home\HomeController@generateFamilies');
Route::get('/discover', 'Home\HomeController@discoverFamilyUnits');
Route::get('/sync', 'Home\HomeController@syncSurveyMonkey');
Route::get('/pdf', 'Home\ReportController@pdf');
Route::get('/report', 'Home\ReportController@index');
Route::get('/dev', 'Home\HomeController@dev');
Route::get('/family/{family_id}', 'Home\FamilyController@show');

//\DB::listen(function($sql, $bindings, $time) {
//    r($sql);
//    r($bindings);
//    r($time);
//});