<?php

namespace Frontend\Modules\ContentBlocks\Engine;

use Frontend\Core\Engine\Model as FrontendModel;

trigger_error(
    'Frontend\Modules\ContentBlocks\Engine is deprecated.
     Switch to doctrine instead.',
    E_USER_DEPRECATED
);

/**
 * In this file we store all generic functions that we will be using in the content_blocks module
 */
class Model
{
    /**
     * Get an item.
     *
     * @param string $id The id of the item to fetch.
     *
     * @return array
     */
    public static function get($id)
    {
        return (array) FrontendModel::getContainer()->get('database')->getRecord(
            'SELECT i.title, i.text, i.template
             FROM content_blocks AS i
             WHERE i.id = ? AND i.status = ? AND i.hidden = ? AND i.language = ?',
            array((int) $id, 'active', 'N', FRONTEND_LANGUAGE)
        );
    }
}
