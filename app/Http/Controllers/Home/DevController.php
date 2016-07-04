<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Ascension\SurveyMonkey;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ref;
use Stash\Driver\FileSystem;
use Stash\Pool;
use Stiphle\Throttle\LeakyBucket;

class DevController extends Controller
{
    private $volunteerHourSurveyId = '';
    private $volunteerHourSurveyId2 = '';
    private $allSurveyIDs = [];
    private $SM, $pool;
    private $throttle, $throttle_id;

    public function __construct()
    {
        ref::config('expLvl', -1);
        ref::config('maxDepth', 0);

        $this->volunteerHourSurveyId = env('SURVEY_MONKEY_SURVEY_ID_1');
        $this->volunteerHourSurveyId2 = env('SURVEY_MONKEY_SURVEY_ID_2');

        $this->allSurveyIDs = [
            $this->volunteerHourSurveyId,
            $this->volunteerHourSurveyId2,
        ];

        $this->throttle = new LeakyBucket;
        $this->throttle_id = 'sm';


        if (! ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $this->SM = new SurveyMonkey(env('SURVEY_MONKEY_API_KEY'), env('SURVEY_MONKEY_ACCESS_TOKEN'));
        $driver = new FileSystem();
        $this->pool = new Pool($driver);
        $logger = new Logger('log');
        $logger->pushHandler((new ErrorLogHandler())->setFormatter(new LineFormatter()));
        $this->pool->setLogger($logger);
        ErrorHandler::register($logger);
//        $logger->log('info', 'test');
//        $this->pool->flush();
    }

    public function dev()
    {
        foreach ($this->SM->getSurveyList()['data']['surveys'] as $list) {
            $id = $list['survey_id'];
            r($this->surveyMonkeyGetSurveyDetails($id));
        }
    }

    private function surveyMonkeyGetSurveyDetails($surveyID)
    {
        $this->throttleSM();

        return $this->SM->getSurveyDetails($surveyID)['data'];
    }

    private function throttleSM()
    {
        $this->throttle->throttle($this->throttle_id, 2, 2000);
    }
}
