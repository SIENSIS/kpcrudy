<?php

namespace Kpcrudy\Demo\Config;

use CodeIgniter\Config\BaseConfig;
use Kpcrudy\Demo\Controllers\Admin\Attendances;
use Kpcrudy\Demo\Controllers\Admin\Categories;
use Kpcrudy\Demo\Controllers\Admin\Events;
use Kpcrudy\Demo\Controllers\Admin\Users;
use Kpcrudy\Demo\Controllers\Agenda;
use Kpcrudy\Demo\Controllers\MyAttendances;
use Kpcrudy\Demo\Controllers\Profile;

/**
 * Demo only: features shown by each page in the "Code" panel.
 *
 * Kept outside the controllers so that they contain only the code needed to
 * build each CRUD. BaseController detects the current controller method and,
 * if it is listed here, publishes its source code and this list to the layout.
 */
class Showcase extends BaseConfig
{
    /**
     * @var array<string, list<string>> "Class::method" => features
     */
    public array $pages = [
        Users::class . '::index' => [
            'Table mode: setTable() detects the primary key, timestamps and soft deletes',
            'Trash (deleted_at): delete, restore, delete permanently',
            'Image field (avatar) with a thumbnail in the list',
            'Password: stored hashed; it can be left empty when editing',
            'Editable N-N relation (interests) through a junction table',
            'Virtual columns with callbackColumn() (full name, registrations)',
            'Rules with {id}: is_unique ignores the record being edited',
            'Read-only field when editing (username)',
            'Per-record permission: you cannot delete yourself',
            'beforeUpdate hook: you cannot remove your own administrator role',
            'Custom bulk actions: activate / deactivate',
            'Row link to the user\'s registrations (URL filter)',
            'CSV import that adds new users and updates existing ones by username (enableImport)',
        ],
        Categories::class . '::index' => [
            'Model mode: setModel() reuses the validation and timestamps of CategoryModel',
            'Translated fields (setTranslations): title and description in English, Catalan and Spanish, stored in a child table with one tab per language in the form',
            'Central permissions: editors can see and edit categories, but not add or delete them (Libraries\\DemoPermissions)',
            'Custom validation messages defined in the model',
            'Table without deleted_at: no trash, deleting is permanent',
            'Colour field with a swatch in the list',
            'Virtual column with the number of events',
            'beforeDelete hook: categories with events cannot be deleted',
            'Row link to the category\'s events',
            'No bulk actions or export (unsetBulk, unsetExport)',
        ],
        Events::class . '::index' => [
            '1-N relations with a display template (category, creator)',
            'Read-only N-N relation: attendees in the details page and the CSV',
            'Image (poster), date and time, decimal, number, textarea and enum',
            'Multiselect field (audience): several values stored as JSON in one column, with a list filter',
            'Central permission resolver (setResource(\'events\') + Libraries\\DemoPermissions): log in as the editor to see fewer buttons, and edit only your own events',
            'Action and bulk action permissions (\'permission\' option): editors do not see Attendees or Cancel, and Publish only applies to their own events',
            'hideFields(): editors do not see the attendees or the creator anywhere (list, details, CSV export, search)',
            'Permission catalogue (Crud::permissionCatalogue()): DemoPermissions checks in development that every operation of the CRUD is declared',
            'afterWrite() hook: the last change (from the form, bulk actions or the import) is shown on the home page',
            'Success and error messages in the demo layout alerts (flashKeys in the demo config)',
            'Occupancy bar without style="" attributes: the demo works with a strict Content-Security-Policy',
            'Posters stored in year/month subfolders of the event (subdir option)',
            'List filters (category, status) and range filters (date, price)',
            'Virtual occupancy column with a progress bar',
            'callbackColumn for status and price; callbackExport for the price in the CSV',
            'Hooks: beforeInsert sets the creator and validates the dates',
            'Share links: signed URLs valid 7 days to a read-only agenda without login (requireSignature, Crud::signUrl)',
            'Masked ids in every URL (maskIds in the demo config): edit/Xk3f9... instead of edit/5',
            'Custom operation (addOperation): "Duplicate" runs on the CRUD route (admin/events/duplicate/{masked id}) with CSRF, permissions and confirmation, and opens the copy with $crud->url(\'edit\', $id)',
            'Custom bulk actions: publish and cancel',
            'Header button to the public agenda',
            'Export columns different from the list columns',
            'CSV import matched by id: export, edit in a spreadsheet and import again',
            'callbackImport: the CSV may say "Free" as the price',
        ],
        Attendances::class . '::index' => [
            'CRUD on a junction table with its own data (status, date, notes)',
            'Two 1-N relations with filtered and sorted options (where / order)',
            'Different fields for adding and editing (addFields / editFields)',
            'Default values when adding (setDefault)',
            'beforeInsert hook: prevents duplicates and checks the capacity',
            'Combinable filters that other CRUDs can link to (?f[event_id]=...)',
            'Custom bulk actions: mark as attended or absent',
            'Table without deleted_at: no trash',
        ],
        Agenda::class . '::index' => [
            'Read-only CRUD (readOnly) on the same table as the admin one',
            'where(): only published, upcoming events, also in the details page and the export',
            'No trash or bulk actions for users (unsetTrash, unsetBulk)',
            'Custom "join" operation (addOperation) on a read-only CRUD: only for events allowed by where(), visible only if there are places left and you are not registered',
            'The operation returns a success message, or $crud->backToList(\'danger\', ...) for an error',
            'Virtual columns with data of the logged-in user (places left, my registration)',
            'Short pagination with custom options (setPerPage)',
        ],
        MyAttendances::class . '::index' => [
            'where(user_id): users only see and change their own registrations',
            'No adding, editing or deleting (unsetAdd, unsetEdit, unsetDelete)',
            'Conditional custom operation (addOperation + visible): cancel only upcoming registrations, on the CRUD route',
            'Custom bulk action: the ids it receives are already limited by where()',
            'CSV export of my registrations',
        ],
        Profile::class . '::index' => [
            'One-record CRUD with where(id = logged-in user)',
            'Custom "profile" theme: only replaces show.php',
            'setBackUrl(): after saving, back to the details page instead of a list',
            'No adding, deleting, trash, export or bulk actions',
            'Limited edit fields (users cannot change their role or username)',
            'Editable N-N relation: my interests',
            'Profile photo upload and optional password change',
        ],
    ];
}
