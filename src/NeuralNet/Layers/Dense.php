<?php

namespace Rubix\ML\NeuralNet\Layers;

use Rubix\ML\NeuralNet\Parameter;
use MathPHP\LinearAlgebra\Matrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use Rubix\ML\NeuralNet\Optimizers\Optimizer;
use Rubix\ML\NeuralNet\ActivationFunctions\Rectifier;
use Rubix\ML\NeuralNet\ActivationFunctions\HyperbolicTangent;
use Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction;
use InvalidArgumentException;

/**
 * Dense
 *
 * Dense layers are fully connected Hidden layers, meaning each neuron is
 * connected to each other neuron in the previous layer. Dense layers are able
 * to employ a variety of Activation Functions that modify the output of each
 * neuron in the layer.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class Dense implements Hidden
{
    /**
     * The number of neurons in this layer.
     *
     * @var int
     */
    protected $neurons;

    /**
     * The function that outputs the activation or implulse of each neuron.
     *
     * @var \Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction
     */
    protected $activationFunction;

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
     * The memoized z matrix.
     *
     * @var \MathPHP\LinearAlgebra\Matrix
     */
    protected $z;

    /**
     * The memoized output activations matrix.
     *
     * @var \MathPHP\LinearAlgebra\Matrix
     */
    protected $computed;

    /**
     * @param  int  $neurons
     * @param  \Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction  $activationFunction
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(int $neurons, ActivationFunction $activationFunction)
    {
        if ($neurons < 1) {
            throw new InvalidArgumentException('The number of neurons cannot be'
                . ' less than 1.');
        }

        $this->neurons = $neurons;
        $this->activationFunction = $activationFunction;
        $this->width = $neurons + 1;
        $this->weights = new Parameter(new Matrix([]));
        $this->input = new Matrix([]);
        $this->z = new Matrix([]);
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
        if ($this->activationFunction instanceof HyperbolicTangent) {
            $r = (6 / $fanIn) ** 0.25;
        } else if ($this->activationFunction instanceof Rectifier) {
            $r = (6 / $fanIn) ** (1 / self::ROOT_2);
        } else  {
            $r = sqrt(6 / $fanIn);
        }

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
     * Compute the input sum and activation of each nueron in the layer and
     * return an activation matrix.
     *
     * @param  \MathPHP\LinearAlgebra\Matrix  $input
     * @return \MathPHP\LinearAlgebra\Matrix
     */
    public function forward(Matrix $input) : Matrix
    {
        $this->input = $input;

        $this->z = $this->weights->w()->multiply($input);

        $temp = $this->z->rowExclude($this->z->getM() - 1);

        $biases = MatrixFactory::one(1, $this->z->getN());

        $this->computed = $this->activationFunction->compute($temp)
            ->augmentBelow($biases);

        return $this->computed;
    }

    /**
     * Calculate the errors and gradients of the layer and update the parameters.
     *
     * @param  \MathPHP\LinearAlgebra\Matrix  $prevWeights
     * @param  \MathPHP\LinearAlgebra\Matrix  $prevErrors
     * @param  \Rubix\ML\NeuralNet\Optimizers\Optimizer  $optimizer
     * @return array
     */
    public function back(Matrix $prevWeights, Matrix $prevErrors, Optimizer $optimizer) : array
    {
        $errors = $this->activationFunction
            ->differentiate($this->z, $this->computed)
            ->hadamardProduct($prevWeights->transpose()->multiply($prevErrors));

        $gradients = $errors->multiply($this->input->transpose());

        $step = $optimizer->step($this->weights, $gradients);

        $this->weights->update($step);

        return [$this->weights->w(), $errors, $step->maxNorm()];
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
