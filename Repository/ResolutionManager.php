<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

/**
 * Solve the conflicts of dependencies by the resolutions.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ResolutionManager
{
    /**
     * @var array
     */
    protected $resolutions;

    /**
     * Constructor.
     *
     * @param array $resolutions The dependency resolutions
     */
    public function __construct(array $resolutions = array())
    {
        $this->resolutions = $resolutions;
    }

    /**
     * Solve the dependency resolutions.
     *
     * @param array $data The data of asset composer package
     *
     * @return array
     */
    public function solveResolutions(array $data)
    {
        $data = $this->doSolveResolutions($data, 'require');
        $data = $this->doSolveResolutions($data, 'require-dev');

        return $data;
    }

    /**
     * Solve the dependency resolutions.
     *
     * @param array  $data    The data of asset composer package
     * @param string $section The dependency section in package
     *
     * @return array
     */
    protected function doSolveResolutions(array $data, $section)
    {
        if (array_key_exists($section, $data) && is_array($data[$section])) {
            foreach ($data[$section] as $dependency => &$range) {
                foreach ($this->resolutions as $resolutionDependency => $resolutionRange) {
                    if ($dependency === $resolutionDependency) {
                        $range = $resolutionRange;
                    }
                }
            }
        }

        return $data;
    }
}
