<?php

declare(strict_types=1);

namespace App\BasicRum;

use App\BasicRum\Bucketizer;
use App\BasicRum\Densityzer;
use App\BasicRum\Statistics\Median;
use App\BasicRum\Date\DayInterval;

class DiagramBuilder
{

    /**
     * @var Report
     */
    protected $report;

    public function __construct(Report $report)
    {
       $this->report = $report;
    }

    /**
     * @param array $data
     * @param int $bucketSize
     * @return array
     */
    public function build(array $data, int $bucketSize = 100)
    {
        $dayInterval = new DayInterval();

        $interval = $dayInterval->generateDayIntervals(
            $data['period']['current_period_from_date'],
            $data['period']['current_period_to_date']
        );

        $samples = [];

        foreach ($interval as $day) {
            $samples = array_merge($samples, $this->report->query($day, $data['perf_metric'], $data['filters']));
        }

        $bucketizer = new Bucketizer();

        $statisticMedian = new Median();

        $buckets = $bucketizer->bucketize($samples, $bucketSize);

        if (!empty($data['decorators']['density']) && $data['decorators']['density'] == 1) {
            $densityzer = new Densityzer();
            $buckets = $densityzer->fillDensity($buckets, count($samples), 4);
        }


        $diagramData = [
            'x' => array_keys($buckets),
            'y' => array_values($buckets),
            'median' => $statisticMedian->calculateMedian($bucketizer->bucketize($samples, 1))
        ];

        return $diagramData;
    }

    /**
     * @param array $data
     * @return array
     */
    public function count(array $data)
    {
        $dayInterval = new DayInterval();

        $interval = $dayInterval->generateDayIntervals(
            $data['period']['current_period_from_date'],
            $data['period']['current_period_to_date']
        );

        $samples = [];

        foreach ($interval as $day) {
            $samples[$day['start']] = count($this->report->query($day, $data['perf_metric'], $data['filters']));
        }

        return $samples;
    }


    /**
     * @param array $data
     *
     * @return array
     */
    public function buildOverTime(array $data)
    {
        $bounceRateReport = new BounceRate($this->report->getDoctrine());

        $bounceRateDay = [];

        $dayInterval = new DayInterval();

        $interval = $dayInterval->generateDayIntervals(
            $data['period']['current_period_from_date'],
            $data['period']['current_period_to_date']
        );

        $bucketizer = new Bucketizer();
        $statisticMedian = new Median();

        $median = [];

        foreach ($interval as $day) {
            $samples = $this->report->query($day, $data['perf_metric'], $data['filters']);
            $median[$day['start']] = $statisticMedian->calculateMedian($bucketizer->bucketize($samples, 1));
            $device = $data['filters']['device_type']['search_value'];
            $bounceRateDay[$day['start']] = $bounceRateReport->bounceRateInPeriod($day['start'], $day['end'], $device);
        }

        $diagramData = [
            'x' => array_keys($median),
            'y' => array_values($median)
        ];

        $bounceRateDiagramData = [
            'x' => array_keys($bounceRateDay),
            'y' => array_values($bounceRateDay)
        ];

        return ['performance' => $diagramData, 'bounce_rate' => $bounceRateDiagramData];
    }

    /**
     * @return array
     */
    public function getNavigationTimings()
    {
        return $this->report->getNavigationTimings();
    }

    /**
     * @return array
     */
    public function getPageTypes()
    {
        return $this->report->getPageTypes();
    }

}