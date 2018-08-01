<?php

namespace Rubix\ML\NeuralNet\Layers;

use Rubix\ML\NeuralNet\Parameter;
use MathPHP\LinearAlgebra\Matrix;
use Rubix\ML\NeuralNet\Optimizers\Optimizer;
use InvalidArgumentException;

/**
 * Linear
 *
 * The Linear Output Layer consists of a single linear neuron that outputs a
 * continuous scalar value useful for Regression problems.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class Linear implements Output
{
    /**
     * The L2 regularization parameter.
     *
     * @var float
     */
    protected $alpha;

    /**
     * The width of the layer. i.e. the number of neurons.
     *
     * @var int
     */
    protected $width;

    /**
     * The weights.
     *
     * @var \Rubix\ML\NeuralNet\Parameter
     */
    protected $weights;

    /**
     * The memoized input matrix.
     *
     * @var \MathPHP\LinearAlgebra\Matrix
     */
    protected $input;

    /**
     * The memoized output activations matrix.
     *
     * @var \MathPHP\LinearAlgebra\Matrix
     */
    protected $computed;

    /**
     * @param  float  $alpha
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(float $alpha = 1e-4)
    {
        if ($alpha < 0) {
            throw new InvalidArgumentException('L2 regularization parameter'
                . ' must be 0 or greater.');
        }

        $this->alpha = $alpha;
        $this->width = 1;
        $this->weights = new Parameter(new Matrix([]));
        $this->input = new Matrix([]);
        $this->computed = new Matrix([]);
    }

    /**
     * @return int
     */
    public function width() : int
    {
        return $this->width;
    }

    /**
     * Initialize the layer by fully connecting each neuron to every input and
     * generating a random weight for each synapse.
     *
     * @param  int  $fanIn
     * @return int
     */
    public function init(int $fanIn) : int
    {
        $r = (6 / sqrt($fanIn));

        $min = (int) (-$r * self::PHI);
        $max = (int) ($r * self::PHI);

        $w = [[]];

        for ($i = 0; $i < $this->width; $i++) {
            for ($j = 0; $j < $fanIn; $j++) {
                $w[$i][$j] = rand($min, $max) / self::PHI;
            }
        }

        $this->weights = new Parameter(new Matrix($w));

        return $this->width;
    }

    /**
     * Compute the input sum and activation of each neuron in the layer and return
     * an activation matrix.
     *
     * @param  \MathPHP\LinearAlgebra\Matrix  $input
     * @return \MathPHP\LinearAlgebra\Matrix
     */
    public function forward(Matrix $input) : Matrix
    {
        $this->input = $input;

        $this->computed = $this->weights->w()->multiply($input);

        return $this->computed;
    }

    /**
     * Calculate the errors and gradients for each output neuron and update.
     *
     * @param  array  $labels
     * @param  \Rubix\ML\NeuralNet\Optimizers\Optimizer  $optimizer
     * @return array
     */
    public function back(array $labels, Optimizer $optimizer) : array
    {
        $penalty = 0.5 * $this->alpha
            * array_sum($this->weights->w()->getRow(0)) ** 2;

        $errors = [];

        foreach ($this->computed->getRow(0) as $i => $activation) {
            $errors[$i] = ($labels[$i] - $activation) + $penalty;
        }

        $errors = new Matrix([$errors]);

        $gradients = $errors->multiply($this->input->transpose());

        $step = $optimizer->step($this->weights, $gradients);

        $this->weights->update($step);

        return [$this->weights->w(), $errors, $step->maxNorm()];
    }

    /**
     * Return the computed activation matrix.
     *
     * @return \MathPHP\LinearAlgebra\Matrix
     */
    public function activations() : Matrix
    {
        return $this->computed->transpose();
    }

    /**
     * @return array
     */
    public function read() : array
    {
        return [
            'weights' => clone $this->weights,
        ];
    }

    /**
     * Restore the parameters of the layer.
     *
     * @param  array  $parameters
     * @return void
     */
    public function restore(array $parameters) : void
    {
        $this->weights = $parameters['weights'];
    }
}
