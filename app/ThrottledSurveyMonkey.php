<?php

namespace App;

use Ascension\SurveyMonkey;
use Stash\Driver\FileSystem;
use Stash\Exception\RuntimeException;
use Stash\Pool;
use Stiphle\Throttle\LeakyBucket;

class ThrottledSurveyMonkey
{
    use DebugDumper;

    private $throttle, $throttle_id;
    /** @var Pool $pool */
    private $pool;

    public function __construct()
    {
        $this->initCache();
        $this->throttle = new LeakyBucket;
        $this->throttle_id = 'sm';
        $this->SM = new SurveyMonkey(env('SURVEY_MONKEY_API_KEY'), env('SURVEY_MONKEY_ACCESS_TOKEN'));
    }

    /**
     * Sets up the SurveyMonkey Cache
     * @throws RuntimeException
     */
    private function initCache()
    {
        $driver = new FileSystem();
        $this->pool = new Pool($driver);
//        $this->pool->flush();
    }

    private function throttleSM()
    {
        $this->throttle->throttle($this->throttle_id, 4, 10000);;
    }

    public function surveyMonkeyGetSurveyDetails($surveyID)
    {
        $item = $this->pool->getItem('/surveyDetails/' . $surveyID);
        $surveyDetails = $item->get();
        if ($item->isMiss()) {
            $this->dl('Requesting survey details from Survey Monkey and saving to cache');
            $item->lock();
            $this->throttleSM();
            $surveyDetails = $this->SM->getSurveyDetails($surveyID);
            $item->set($surveyDetails);
            return $surveyDetails;
        }

        $this->dl('Reusing cached survey details');
        return $surveyDetails;
    }

    public function surveyMonkeyGetRespondentList($surveyID, $array)
    {
        $item = $this->pool->getItem('/reslist/' . $surveyID);
        $respondentsToRequest = [];
        $respondentList = $item->get();
        $this->dl($item->isMiss());
        if ($item->isMiss()) {
            $this->dl('Requesting respondent list from Survey Monkey and saving to cache');
            $item->lock();
            $this->throttleSM();
            $respondentList = $this->SM->getRespondentList($surveyID, ['fields' => ['date_modified']]);
            $item->set($respondentList);
        } else {
            $this->dl('Reusing cached respondent list');
        }

        return $respondentList;
    }

    public function surveyMonkeyGetResponses($surveyID, $respondentsToRequest)
    {
        $item = $this->pool->getItem('/responses/' . $surveyID);
        $responses = $item->get();
        if ($item->isMiss()) {
            $this->dl('Requesting responses from Survey Monkey and saving to cache');
            $item->lock();
            $this->throttleSM();
            $responses = $this->SM->getResponses($surveyID, $respondentsToRequest);
            $item->set($responses);
        } else {
            $this->dl('Reusing cached respondent list');
        }
        return $responses;
    }



}