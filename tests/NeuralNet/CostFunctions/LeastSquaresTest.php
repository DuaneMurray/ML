<?php

namespace Rubix\Tests\NeuralNet\CostFunctions;

use Rubix\ML\Other\Structures\Matrix;
use Rubix\ML\NeuralNet\CostFunctions\LeastSquares;
use Rubix\ML\NeuralNet\CostFunctions\CostFunction;
use PHPUnit\Framework\TestCase;

class LeastSquaresTest extends TestCase
{
    protected $costFunction;

    protected $expected;

    protected $activation;

    protected $delta;

    public function setUp()
    {
        $this->expected = new Matrix([[36.], [22.], [18.], [41.5], [38.]]);

        $this->activation = new Matrix([[33.98], [20.], [4.6], [44.2], [38.5]]);

        $this->delta = new Matrix([
            [2.0402000000000062],
            [2.],
            [89.78],
            [3.6450000000000076],
            [0.125],
        ]);

        $this->costFunction = new LeastSquares();
    }

    public function test_build_cost_function()
    {
        $this->assertInstanceOf(LeastSquares::class, $this->costFunction);
        $this->assertInstanceOf(CostFunction::class, $this->costFunction);
    }

    public function test_compute()
    {
        $cost = $this->costFunction
            ->compute($this->expected, $this->activation)
            ->asArray();

        $this->assertEquals($this->delta->asArray(), $cost);
    }

    public function test_differentiate()
    {
        $derivative = $this->costFunction
            ->differentiate($this->expected, $this->activation, $this->delta)
            ->asArray();

        $outcome = [
            [-2.020000000000003],
            [-2.0],
            [-13.4],
            [2.700000000000003],
            [0.5],
        ];

        $this->assertEquals($outcome, $derivative);
    }
}