<?php

namespace Common\Core\Twig;

use Common\Core\Form;
use Common\Core\Model;
use Common\ModulesSettings;
use SpoonForm;
use Twig\Environment;

/**
 * This is a twig template wrapper
 * that glues spoon libraries and code standards with twig.
 */
abstract class BaseTwigTemplate extends Environment
{
    /**
     * @var string
     */
    protected $language;

    /**
     * Should we add slashes to each value?
     *
     * @var bool
     */
    protected $addSlashes = false;

    /**
     * Debug mode.
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * List of form objects.
     *
     * @var Form[]
     */
    protected $forms = [];

    /**
     * List of assigned variables.
     *
     * @var array
     */
    protected $variables = [];

    /**
     * @var ModulesSettings
     */
    protected $forkSettings;

    /**
     * List of globals that have been assigned at runtime
     *
     * @var array
     */
    protected $runtimeGlobals = [];

    public function assign(string $key, $values): void
    {
        $this->variables[$key] = $values;
    }

    public function assignGlobal(string $key, $value): void
    {
        $this->runtimeGlobals[$key] = $value;
    }

    /**
     * Assign an entire array with keys & values.
     *
     * @param array $variables This array with keys and values will be used to search and replace in the template file.
     * @param string|null $index
     */
    public function assignArray(array $variables, string $index = null): void
    {
        // artifacts?
        if (!empty($index) && isset($variables['Core'])) {
            unset($variables['Core']);
            $variables = [$index => $variables];
        }

        // merge the variables array_merge might be to slow for bigger sites
        // as array_merge tend to slow down at +100 keys
        foreach ($variables as $key => $val) {
            $this->variables[$key] = $val;
        }
    }

    public function addForm(SpoonForm $form): void
    {
        $this->forms[$form->getName()] = $form;
    }

    /**
     * Retrieves the already assigned variables.
     *
     * @return array
     */
    public function getAssignedVariables(): array
    {
        return $this->variables;
    }

    /** @todo Refactor out constants #1106
     * We need to deprecate this asap
     */
    protected function startGlobals()
    {
        // some old globals
        $this->addGlobal('var', '');
        $this->addGlobal('CRLF', "\n");
        $this->addGlobal('TAB', "\t");
        $this->addGlobal('now', time());
        $this->addGlobal('LANGUAGE', $this->language);
        $this->addGlobal('is'.strtoupper($this->language), true);
        $this->addGlobal('debug', $this->debugMode);

        $this->addGlobal('timestamp', time());

        // get all defined constants
        $constants = get_defined_constants(true);

        // remove protected constants aka constants that should not be used in the template
        foreach ($constants['user'] as $key => $value) {
            $this->addGlobal($key, $value);
        }

        /* Setup Backend for the Twig environment. */
        if (!$this->forkSettings || !Model::getContainer()->getParameter('fork.is_installed')) {
            return;
        }

        $this->addGlobal('timeFormat', $this->forkSettings->get('Core', 'time_format'));
        $this->addGlobal('dateFormatShort', $this->forkSettings->get('Core', 'date_format_short'));
        $this->addGlobal('dateFormatLong', $this->forkSettings->get('Core', 'date_format_long'));

        // old theme checker
        if ($this->forkSettings->get('Core', 'theme') !== null) {
            $this->addGlobal('THEME', $this->forkSettings->get('Core', 'theme', 'Fork'));
            $this->addGlobal(
                'THEME_URL',
                '/src/Frontend/Themes/'.$this->forkSettings->get('Core', 'theme', 'Fork')
            );
        }

        // settings
        $this->addGlobal(
            'SITE_TITLE',
            $this->forkSettings->get('Core', 'site_title_'.$this->language, SITE_DEFAULT_TITLE)
        );
        $this->addGlobal(
            'SITE_URL',
            SITE_URL
        );
        $this->addGlobal(
            'SITE_DOMAIN',
            SITE_DOMAIN
        );

        // facebook stuff
        if ($this->forkSettings->get('Core', 'facebook_admin_ids', null) !== null) {
            $this->addGlobal(
                'FACEBOOK_ADMIN_IDS',
                $this->forkSettings->get('Core', 'facebook_admin_ids', null)
            );
        }
        if ($this->forkSettings->get('Core', 'facebook_app_id', null) !== null) {
            $this->addGlobal(
                'FACEBOOK_APP_ID',
                $this->forkSettings->get('Core', 'facebook_app_id', null)
            );
        }
        if ($this->forkSettings->get('Core', 'facebook_app_secret', null) !== null) {
            $this->addGlobal(
                'FACEBOOK_APP_SECRET',
                $this->forkSettings->get('Core', 'facebook_app_secret', null)
            );
        }

        // twitter stuff
        if ($this->forkSettings->get('Core', 'twitter_site_name', null) !== null) {
            // strip @ from twitter username
            $this->addGlobal(
                'TWITTER_SITE_NAME',
                ltrim($this->forkSettings->get('Core', 'twitter_site_name', null), '@')
            );
        }
    }

    /**
     * Should we execute addSlashed on the locale?
     *
     * @param bool $enabled Enable addslashes.
     */
    public function setAddSlashes(bool $enabled = true): void
    {
        $this->addSlashes = $enabled;
    }

    public function render($template, array $variables = []): string
    {
        if (!empty($this->forms)) {
            foreach ($this->forms as $form) {
                // using assign to pass the form as global
                $this->assignGlobal('form_' . $form->getName(), $form);
            }
        }

        return $this->render($template, array_merge($this->runtimeGlobals, $variables));
    }
}
