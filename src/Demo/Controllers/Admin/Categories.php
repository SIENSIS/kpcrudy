<?php

namespace Kpcrudy\Demo\Controllers\Admin;

use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Controllers\BaseController;
use Kpcrudy\Demo\Models\CategoryModel;

class Categories extends BaseController
{
    /**
     * Categories, managed through a CodeIgniter model. The visible texts are
     * translated in a child table (one row per language).
     */
    public function index()
    {
        $crud = new Crud(new CrudConfig());
        $crud->setModel(CategoryModel::class)
            ->setResource('categories')
            ->setSubject('category', 'Categories')
            // title and description live in kpdemo_category_translations (category_id, locale, ...).
            // English is required; Catalan and Spanish can be left empty
            ->setTranslations('kpdemo_category_translations', 'category_id', 'locale', ['title', 'description'], [
                'en' => 'English',
                'ca' => 'Català',
                'es' => 'Español',
            ], [
                'required' => ['en'],
                'types'    => ['description' => 'textarea'],
                // 'required' only applies to the required languages (English)
                'rules'    => ['title' => 'required|min_length[3]|max_length[80]'],
            ])
            ->columns(['color', 'name', 'title', 'description', 'events'])
            ->fields(['name', 'color', 'title', 'description'])
            ->displayAs(['name' => 'Internal name', 'color' => 'Colour', 'title' => 'Title', 'description' => 'Description', 'events' => 'Events', 'created_at' => 'Created', 'updated_at' => 'Updated'])
            ->setHelp('name', 'Unique name used in the lists of other pages.')
            ->fieldType('color', 'color')
            ->callbackColumn('events', static function ($value, array $row) {
                $total = db_connect()->table('kpdemo_events')->where('category_id', $row['id'])->where('deleted_at', null)->countAllResults();

                return '<span class="badge rounded-pill text-bg-light border">' . $total . '</span>';
            })
            ->addAction('Events', 'kpcrudy-demo/admin/events?f[category_id]={id}', ['icon' => 'bi bi-calendar-event', 'class' => 'btn-outline-info'])
            ->beforeDelete(static function (string $id) {
                // Includes the events in the trash: the foreign key would block the delete anyway
                $total = db_connect()->table('kpdemo_events')->where('category_id', $id)->countAllResults();

                return $total > 0 ? "This category cannot be deleted: it has {$total} events." : null;
            })
            ->defaultOrder('name')
            ->unsetBulk()
            ->unsetExport();

        return $crud->render();
    }
}
