<?php

namespace Backend\Modules\Search\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionDelete as BackendBaseActionDelete;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Form\Type\DeleteType;
use Backend\Modules\Search\Engine\Model as BackendSearchModel;

/**
 * This action will delete a synonym
 */
class DeleteSynonym extends BackendBaseActionDelete
{
    public function execute(): void
    {
        parent::execute();

        $deleteForm = $this->createForm(
            DeleteType::class,
            null,
            ['module' => $this->getModule(), 'action' => 'DeleteSynonym']
        );
        $deleteForm->handleRequest($this->getRequest());
        if (!$deleteForm->isSubmitted() || !$deleteForm->isValid()) {
            $this->redirect(BackendModel::createURLForAction(
                'Synonyms',
                null,
                null,
                ['error' => 'something-went-wrong']
            ));

            return;
        }
        $deleteFormData = $deleteForm->getData();

        $id = (int) $deleteFormData['id'];

        if ($id === 0 || !BackendSearchModel::existsSynonymById($id)) {
            $this->redirect(BackendModel::createURLForAction('Synonyms', null, null, ['error' => 'non-existing']));

            return;
        }

        $synonym = (array) BackendSearchModel::getSynonym($id);
        BackendSearchModel::deleteSynonym($id);

        $this->redirect(BackendModel::createURLForAction(
            'Synonyms',
            null,
            null,
            ['report' => 'deleted-synonym', 'var' => $synonym['term']]
        ));
    }
}
