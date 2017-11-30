<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
function GUID()
{
    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('users', function()
{
    return 'Users!';
});

/**Ember Update row, with row id as input */
Route::patch('tasks', function(Request $request)
{
	try
	{
		$url = $request->fullUrl() ;
		$querystring = parse_url($url, PHP_URL_QUERY);
		parse_str($querystring, $vars);
		$sID = $vars['id'] ;
		$oData = $request->all() ;
		$oData["data"]["id"] = $sID;/** Create a unuiqe ID for Response*/
		$oAtt = $oData["data"]["attributes"] ;
		$sTitle = $oAtt["title"] ;
		$bIsCompleted = $oAtt["isCompleted"] ;
		
		/**Update DB Task by ID */
		DB::table('tasks')
		->where('id', $sID)
		->update(['id' => $sID, 'title' => $sTitle, 'isCompleted' => $bIsCompleted ]);

		/*Reply with HTTP Request data*/
		return $oData ;
	}
	catch (Exception $e)
	{
		//print_r($e) ;
	}

	return '{"data":{"id":"0"},"type":"task"}' ;
});

/**Ember deleted row, with row id as input */
Route::delete('tasks', function(Request $request)
{
	try
	{
		$url = $request->fullUrl() ;
		$querystring = parse_url($url, PHP_URL_QUERY);
		parse_str($querystring, $vars);
		$sID = $vars['id'] ;
		/** Delete Task from DB by ID */
		DB::table('tasks')->where('id', $sID)->delete();
	}
	catch (Exception $e)
	{
		//print_r($e) ;
	}
	/**REsponse with deleted id */
	return '{"data":{"id":"'.$sID.'","type":"task"}}' ;/*Rescue id */
});

Route::post('tasks', function(Request $request)/**Ember sends new row */
{
	try
	{
		if ($request->filled('data')) 
		{
			$oData = $request->all() ;
			$sID = GUID();
			$oData["data"]["id"] = $sID;/** Create a unuiqe ID for Response*/
			$oAtt = $oData["data"]["attributes"] ;
			$sTitle = $oAtt["title"] ;
			$bIsCompleted = $oAtt["isCompleted"] ;

			/**Insert new task to DB */
			DB::table('tasks')->insert(	['id' => $sID, 'title' => $sTitle, 'isCompleted' => $bIsCompleted ]);
			/**Response with Sent data and new task ID */
			return $oData ;
		}
	}
	catch (Exception $e)
	{
		//print_r($e) ;
	}
	
	return '{"data":{"id":"0"},"type":"task"}' ;
});

Route::get('tasks', function()
{
	class DataItems
	{
		public $data;
	}
	
	class Data
	{
		public $type;
		public $id;
		public $attributes;
	}
	class Task
	{
		public $title;
		public $isCompleted;
	}
	
	$oDataItems = new DataItems();
	$oDataItems->data = array();

		try
		{
			$oDBData = DB::table('tasks')->select('id', 'title', 'isCompleted')->get();
			
			foreach ($oDBData as $index => $oRow)
			{
				$oData = new Data();
				$oTask = new Task();
				$oTask->title = $oRow->title ;
				$oTask->isCompleted = $oRow->isCompleted;
				
				$oData->attributes = $oTask ;
				$oData->type = "task" ;
				$oData->id = $oRow->id;
				
				$oDataItems->data[$index] = $oData ;
			}

			return json_encode($oDataItems) ;
		}
		catch (Exception $e)
		{
			//print_r($e) ;
		}
		
		return '{"data":{"id":"0"},"type":"task"}' ;
});