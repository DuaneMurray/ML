<?php

include dirname(__DIR__) . '/vendor/autoload.php';

use Rubix\ML\Pipeline;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\NeuralNet\Layers\Dense;
use Rubix\ML\Reports\AggregateReport;
use Rubix\ML\Reports\ConfusionMatrix;
use Rubix\ML\Reports\PredictionSpeed;
use Rubix\ML\NeuralNet\Optimizers\Adam;
use Rubix\ML\Transformers\OneHotEncoder;
use Rubix\ML\Reports\MulticlassBreakdown;
use Rubix\ML\Transformers\LambdaFunction;
use Rubix\ML\CrossValidation\Metrics\MCC;
use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Classifiers\MultiLayerPerceptron;
use Rubix\ML\Transformers\SparseRandomProjector;
use Rubix\ML\NeuralNet\ActivationFunctions\SELU;
use League\Csv\Reader;

echo '╔═════════════════════════════════════════════════════╗' . "\n";
echo '║                                                     ║' . "\n";
echo '║ Mushroom Classifier using Multi Layer Perceptron    ║' . "\n";
echo '║                                                     ║' . "\n";
echo '╚═════════════════════════════════════════════════════╝' . "\n";

$reader = Reader::createFromPath(dirname(__DIR__) . '/datasets/mushrooms.csv')
    ->setDelimiter(',')->setEnclosure('"')->setHeaderOffset(0);

$header = [
    'cap-shape', 'cap-surface', 'cap-color', 'bruises', 'odor',
    'gill-attachment', 'gill-spacing', 'gill-size', 'gill-color', 'stalk-shape',
    'stalk-root', 'stalk-surface-above-ring', 'stalk-surface-below-ring',
    'stalk-color-above-ring', 'stalk-color-below-ring', 'veil-type',
    'veil-color', 'ring-number', 'ring-type', 'spore-print-color', 'population',
    'habitat',
];

$samples = iterator_to_array($reader->getRecords($header));

$labels = iterator_to_array($reader->fetchColumn('class'));

$dataset = new Labeled($samples, $labels);

$hidden = [
    new Dense(20, new SELU()),
    new Dense(20, new SELU()),
    new Dense(20, new SELU()),
];

$estimator = new Pipeline(new MultiLayerPerceptron($hidden, 50, new Adam(0.001),
    1e-4, new MCC(), 0.1, 3, 1e-3, 20), [
        new OneHotEncoder(),
        new SparseRandomProjector(30),
        new ZScaleStandardizer(),
    ]);

$report = new AggregateReport([
    new ConfusionMatrix(),
    new MulticlassBreakdown(),
    new PredictionSpeed(),
]);

list($training, $testing) = $dataset->randomize()->stratifiedSplit(0.8);

$start = microtime(true);

$estimator->train($training);

echo 'Training took ' . (string) (microtime(true) -  $start) . ' seconds' . "\n";

var_dump($estimator->scores());

var_dump($report->generate($estimator, $testing));

var_dump($estimator->proba($testing->head(3)));
