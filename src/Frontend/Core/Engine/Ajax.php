<?php

namespace Frontend\Core\Engine;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;

use Frontend\Core\Engine\Base\AjaxAction as FrontendBaseAJAXAction;

/**
 * FrontendAJAX
 * This class will handle AJAX-related stuff
 *
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 * @author Davy Hellemans <davy.hellemans@netlash.com>
 * @author Dave Lens <dave.lens@wijs.be>
 * @author Dieter Vanden Eynde <dieter.vandeneynde@wijs.be>
 */
class Ajax extends \KernelLoader implements \ApplicationInterface
{
    /**
     * The action
     *
     * @var    string
     */
    private $action;

    /**
     * @var AjaxAction
     */
    private $ajaxAction;

    /**
     * The language
     *
     * @var    string
     */
    private $language;

    /**
     * The module
     *
     * @var    string
     */
    private $module;

    /**
     * Output generated by the action.
     *
     * @var array
     */
    private $output;

    /**
     * @return Response
     */
    public function display()
    {
        return $this->output;
    }

    /**
     * This method exists because the service container needs to be set before
     * the request's functionality gets loaded.
     */
    public function initialize()
    {
        // get vars
        $module = isset($_POST['fork']['module']) ? $_POST['fork']['module'] : '';
        if ($module == '' && isset($_GET['module'])) {
            $module = $_GET['module'];
        }
        $action = isset($_POST['fork']['action']) ? $_POST['fork']['action'] : '';
        if ($action == '' && isset($_GET['action'])) {
            $action = $_GET['action'];
        }
        $language = isset($_POST['fork']['language']) ? $_POST['fork']['language'] : '';
        if ($language == '' && isset($_GET['language'])) {
            $language = $_GET['language'];
        }
        if ($language == '') {
            $language = SITE_DEFAULT_LANGUAGE;
        }

        try {
            $this->setModule($module);
            $this->setAction($action);
            $this->setLanguage($language);

            if (extension_loaded('newrelic')) {
                newrelic_name_transaction('ajax::' . $module . '::' . $action);
            }

            $this->ajaxAction = new AjaxAction($this->getKernel(), $this->getAction(), $this->getModule());
            $this->output = $this->ajaxAction->execute();
        } catch (Exception $e) {
            if (Model::getContainer()->getParameter('kernel.debug')) {
                $message = $e->getMessage();
            } else {
                $message = Model::getContainer()->getParameter('fork.debug_message');
            }

            $this->ajaxAction = new FrontendBaseAJAXAction($this->getKernel(), '', '');
            $this->ajaxAction->output(FrontendBaseAJAXAction::ERROR, null, $message);
            $this->output = $this->ajaxAction->execute();
        }
    }

    /**
     * Get the loaded action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the loaded module
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set action
     *
     * @param string $value The action that should be executed.
     */
    public function setAction($value)
    {
        // check if module is set
        if ($this->getModule() === null) {
            throw new Exception('Module has not yet been set.');
        }

        // grab the file
        $finder = new Finder();
        $finder->name($value . '.php');
        if ($this->getModule() == 'core') {
            $finder->in(FRONTEND_PATH . '/Core/Ajax/');
        } else {
            $finder->in(FRONTEND_PATH . '/Modules/' . $this->getModule() . '/Ajax/');
        }

        // validate
        if (count($finder->files()) != 1) {
            $fakeAction = new FrontendBaseAJAXAction($this->getKernel(), '', '');
            $fakeAction->output(FrontendBaseAJAXAction::BAD_REQUEST, null, 'Action not correct.');
        }

        // set property
        $this->action = (string) $value;
    }

    /**
     * Set the language
     *
     * @param string $value The (interface-)language, will be used to parse labels.
     */
    public function setLanguage($value)
    {
        // get the possible languages
        $possibleLanguages = Language::getActiveLanguages();

        // validate
        if (!in_array($value, $possibleLanguages)) {
            // only 1 active language?
            if (!Model::getContainer()->getParameter('site.multilanguage') && count($possibleLanguages) == 1) {
                $this->language = array_shift(
                    $possibleLanguages
                );
            } else {
                // multiple languages available but none selected
                throw new Exception('Language invalid.');
            }
        } else {
            // language is valid: set property
            $this->language = (string) $value;
        }

        // define constant
        defined('FRONTEND_LANGUAGE') || define('FRONTEND_LANGUAGE', $this->language);

        // set the locale (we need this for the labels)
        Language::setLocale($this->language);
    }

    /**
     * Set module
     *
     * @param string $value The module, wherefore an action will be executed.
     */
    public function setModule($value)
    {
        // get the possible modules
        $possibleModules = Model::getModules();

        // validate
        if (!in_array($value, $possibleModules)) {
            // create fake action
            $fakeAction = new FrontendBaseAJAXAction($this->getKernel(), '', '');

            // output error
            $fakeAction->output(FrontendBaseAJAXAction::BAD_REQUEST, null, 'Module not correct.');
        }

        // set property
        $this->module = (string) $value;
    }
}
