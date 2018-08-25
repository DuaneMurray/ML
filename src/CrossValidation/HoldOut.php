<?php

namespace Rubix\ML\CrossValidation;

use Rubix\ML\Estimator;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\CrossValidation\Metrics\Metric;
use InvalidArgumentException;

class HoldOut implements Validator
{
    /**
     * The hold out ratio. i.e. the ratio of samples to use for testing.
     *
     * @var float
     */
    protected $ratio;

    /**
     * Should we stratify the dataset before splitting?
     *
     * @var bool
     */
    protected $stratify;

    /**
     * @param  float  $ratio
     * @param  bool  $stratify
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(float $ratio = 0.2, bool $stratify = false)
    {
        if ($ratio < 0.01 or $ratio > 1.) {
            throw new InvalidArgumentException('Holdout ratio must be'
                . ' between 0.01 and 1.0.');
        }

        $this->ratio = $ratio;
        $this->stratify = $stratify;
    }

    /**
     * Run a single training and testing round where the ratio determines the
     * number of samples held out for testing.
     *
     * @param  \Rubix\ML\Estimator  $estimator
     * @param  \Rubix\ML\Datasets\Labeled  $dataset
     * @param  \Rubix\ML\CrossValidation\Metrics\Metric  $metric
     * @return float
     */
    public function test(Estimator $estimator, Labeled $dataset, Metric $metric) : float
    {
        if ($this->stratify === true) {
            list($training, $testing) = $dataset->stratifiedSplit(1. - $this->ratio);
        } else {
            list($training, $testing) = $dataset->randomize()->split(1. - $this->ratio);
        }

        $estimator->train($training);

        $score = $metric->score($estimator, $testing);

        return $score;
    }
}
