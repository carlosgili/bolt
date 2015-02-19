<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Installer;
use Silex\Application;

/**
 * Composer package install class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class InstallPackage
{
    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @param $app Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Install packages
     *
     * @return integer 0 on success or a positive error code on failure
     */
    public function execute()
    {
        $composer = $this->app['extend.manager']->getComposer();
        $io = $this->app['extend.manager']->getIO();

        $install = Installer::create($io, $composer);
        $config = $composer->getConfig();
        $optimize = $config->get('optimize-autoloader');

        $preferSource = false;
        $preferDist = true;

        switch ($config->get('preferred-install')) {
            case 'source':
                $preferSource = true;
                break;
            case 'dist':
                $preferDist = true;
                break;
            case 'auto':
            default:
                // noop
                break;
        }

        if ($config->get('prefer-source') || $config->get('prefer-dist')) {
            $preferSource = $config->get('prefer-source');
            $preferDist = $config->get('prefer-dist');
        }

        try {
            $install
                ->setDryRun($this->app['extend.manager']->getOption('dryrun'))
                ->setVerbose($this->app['extend.manager']->getOption('verbose'))
                ->setPreferSource($preferSource)
                ->setPreferDist($preferDist)
                ->setDevMode(!$this->app['extend.manager']->getOption('nodev'))
                ->setDumpAutoloader(!$this->app['extend.manager']->getOption('noautoloader'))
                ->setRunScripts(!$this->app['extend.manager']->getOption('noscripts'))
                ->setOptimizeAutoloader($optimize)
                ->setIgnorePlatformRequirements($this->app['extend.manager']->getOption('ignoreplatformreqs'))
                ->setUpdate(true);

            return $install->run();
        } catch (\Exception $e) {
            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
            $this->app['logger.system']->addCritical($msg, array('event' => 'exception', 'exception' => $e));
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
