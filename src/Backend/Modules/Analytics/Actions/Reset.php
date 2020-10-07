<?php

namespace Backend\Modules\Analytics\Actions;

use Backend\Core\Engine\Base\Action;
use Backend\Core\Engine\Model;
use Common\ModulesSettings;

/**
 * This is the reset-action. It will remove your coupling with analytics
 */
final class Reset extends Action
{
    public function execute(): void
    {
        $this->checkToken();

        $this->get(ModulesSettings::class)->delete($this->getModule(), 'certificate');
        $this->get(ModulesSettings::class)->delete($this->getModule(), 'email');
        $this->get(ModulesSettings::class)->delete($this->getModule(), 'account');
        $this->get(ModulesSettings::class)->delete($this->getModule(), 'web_property_id');
        $this->get(ModulesSettings::class)->delete($this->getModule(), 'profile');

        $this->redirect(Model::createUrlForAction('Settings'));
    }
}
