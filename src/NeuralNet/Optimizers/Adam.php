<?php

namespace Rubix\ML\NeuralNet\Optimizers;

use Rubix\ML\NeuralNet\Parameter;
use MathPHP\LinearAlgebra\Matrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use InvalidArgumentException;
use SplObjectStorage;

/**
 * Adam
 *
 * Short for Adaptive Momentum Estimation, the Adam Optimizer uses both Momentum
 * and RMS properties to achieve a balance of velocity and stability.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class Adam implements Optimizer
{
    /**
     * The learning rate. i.e. the master step size.
     *
     * @var float
     */
    protected $rate;

    /**
     * The decay rate of the momentum property.
     *
     * @var float
     */
    protected $momentumDecay;

    /**
     * The decay rate of the RMS property.
     *
     * @var float
     */
    protected $rmsDecay;

    /**
     * The smoothing parameter. i.e. a tiny number that helps provide numerical
     * smoothing and stability.
     *
     * @var float
     */
    protected $epsilon;

    /**
     * The memoized velocity matrices.
     *
     * @var \SplObjectStorage
     */
    protected $velocities;

    /**
     * The rolling sum of squared gradient matrices.
     *
     * @var \SplObjectStorage
     */
    protected $cache;

    /**
     * @param  float  $rate
     * @param  float  $momentumDecay
     * @param  float  $rmsDecay
     * @param  float  $epsilon
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(float $rate = 0.001, float $momentumDecay = 0.9,
                                float $rmsDecay = 0.999, float $epsilon = 1e-8)
    {
        if ($rate <= 0.0) {
            throw new InvalidArgumentException('The learning rate must be set'
                . ' to a positive value.');
        }

        if ($momentumDecay < 0.0 or $momentumDecay > 1.0) {
            throw new InvalidArgumentException('Momentum decay must be between'
                . ' 0 and 1.');
        }

        if ($rmsDecay < 0.0 or $rmsDecay > 1.0) {
            throw new InvalidArgumentException('RMS decay rate must be between'
                . ' 0 and 1.');
        }

        if ($epsilon === 0.0) {
            throw new InvalidArgumentException('Epsilon cannot be 0.');
        }

        $this->rate = $rate;
        $this->momentumDecay = $momentumDecay;
        $this->rmsDecay = $rmsDecay;
        $this->epsilon = $epsilon;
        $this->velocities = new SplObjectStorage();
        $this->cache = new SplObjectStorage();
    }

    /**
     * Calculate a gradient descent step for a given parameter.
     *
     * @param  \Rubix\ML\NeuralNet\Parameter  $parameter
     * @param  \MathPHP\LinearAlgebra\Matrix  $gradients
     * @return \MathPHP\LinearAlgebra\Matrix
     */
    public function step(Parameter $parameter, Matrix $gradients) : Matrix
    {
        if ($this->velocities->contains($parameter)) {
            $velocities = $this->velocities[$parameter];
            $cache = $this->cache[$parameter];
        } else {
            $m = $parameter->w()->getM();
            $n = $parameter->w()->getN();
            
            $velocities = MatrixFactory::zero($m, $n);
            $cache = MatrixFactory::zero($m, $n);

            $this->velocities->attach($parameter, $velocities);
            $this->cache->attach($parameter, $cache);
        }

        $velocities = $velocities
            ->scalarMultiply($this->momentumDecay)
            ->add($gradients->scalarMultiply(1 - $this->momentumDecay));

        $cache = $cache
            ->scalarMultiply($this->rmsDecay)
            ->add($gradients->hadamardProduct($gradients)->scalarMultiply(1 - $this->rmsDecay));

        $step = [[]];

        foreach ($velocities->getMatrix() as $i => $row) {
            foreach ($row as $j => $velocity) {
                $step[$i][$j] = $this->rate * $velocity
                    / (sqrt($cache[$i][$j]) + $this->epsilon);
            }
        }

        $this->velocities[$parameter] = $velocities;
        $this->cache[$parameter] = $cache;

        return new Matrix($step);
    }
}
