<?php

Route::get('/', 'Home\HomeController@index');
Route::get('/csv', 'Home\CSVController@csv');
Route::get('/hash', 'Home\HashController@generateFamilies');
Route::get('/discover', 'Home\DiscoverController@discoverFamilyUnits');
Route::get('/sync', 'Home\SyncController@syncSurveyMonkey');
Route::get('/pdf', 'Home\ReportController@pdf');
Route::get('/report', 'Home\ReportController@index');
Route::get('/report_test', 'Home\ReportController@test');
Route::get('/dev', 'Home\DevController@dev');
Route::get('/family/{family_id}', 'Home\FamilyController@show');
//\Debugbar::disable();

//\DB::listen(function($sql, $bindings, $time) {
//    r($sql);
//    r($bindings);
//    r($time);
//});
