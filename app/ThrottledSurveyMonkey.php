<?php

namespace App;

use Ascension\SurveyMonkey;
use Stiphle\Throttle\LeakyBucket;

class ThrottledSurveyMonkey
{
    private $throttle, $throttle_id;

    public function __construct()
    {
        $this->throttle = new LeakyBucket;
        $this->throttle_id = 'sm';
        $this->SM = new SurveyMonkey(env('SURVEY_MONKEY_API_KEY'), env('SURVEY_MONKEY_ACCESS_TOKEN'));
    }

    private function throttleSM()
    {
        $this->throttle->throttle($this->throttle_id, 2, 2000);;
    }

    public function surveyMonkeyGetSurveyDetails($surveyID)
    {
        $this->throttleSM();
        return $this->SM->getSurveyDetails($surveyID)['data'];
    }

    public function surveyMonkeyGetRespondentList($surveyID, $array)
    {
        $this->throttleSM();
        return $this->SM->getRespondentList($surveyID, $array);
    }

    public function surveyMonkeyGetResponses($surveyID, $respondentsToRequest)
    {
        $this->throttleSM();
        return $this->SM->getResponses($surveyID, $respondentsToRequest);
    }



}