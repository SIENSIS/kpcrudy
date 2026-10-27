<?php

namespace Kpcrudy\Demo\Controllers;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Demo;

/**
 * Shared agenda: a read-only page that anyone can open WITHOUT logging in,
 * but only through a signed link (requireSignature). The links are created
 * from the events administration ("Share agenda" and the "Share" row action)
 * and expire after a week.
 */
class Shared extends BaseController
{
    public const TTL = 7 * 86400;

    public function agenda()
    {
        $crud = new Crud(new CrudConfig());
        $crud->setTable('kpdemo_events')
            ->setSubject('event', 'Shared agenda')
            // where() limits what the link gives access to: only upcoming published events
            ->where('status', 'published')
            ->where('starts_at >=', date('Y-m-d H:i:s'))
            ->readOnly()
            ->unsetTrash()
            ->unsetBulk()
            ->unsetExport()
            // Every page (list, details...) needs a valid signature. The links the
            // CRUD generates (pages, details) are signed with the same expiry.
            ->requireSignature('*')
            // No demo navbar: the visitor is not logged in
            ->setLayout('default')
            ->columns(['title', 'category_id', 'starts_at', 'location', 'price'])
            ->showFields(['title', 'category_id', 'summary', 'description', 'starts_at', 'ends_at', 'location', 'price'])
            ->displayAs([
                'title'       => 'Title',
                'category_id' => 'Category',
                'summary'     => 'Summary',
                'description' => 'Description',
                'starts_at'   => 'Date',
                'ends_at'     => 'Ends',
                'location'    => 'Location',
                'price'       => 'Price',
            ])
            ->setRelation('category_id', 'kpdemo_categories', 'name')
            ->callbackColumn('price', static fn ($value) => (float) $value > 0 ? '€' . esc(number_format((float) $value, 2)) : 'Free')
            ->defaultOrder('starts_at');

        return $crud->render();
    }

    /**
     * Signed link to the shared agenda, or to one event if $eventId is given.
     * Only the path is signed (lockQuery false): the visitor can still search
     * and change pages, but cannot change the event id or the expiry.
     */
    public static function link(int|string|null $eventId = null): string
    {
        $path = $eventId === null
            ? 'shared/agenda'
            : 'shared/agenda/show/' . Demo::maskId('kpdemo_events', $eventId);

        return Crud::signUrl(Demo::url($path), self::TTL, false, Demo::config());
    }
}
