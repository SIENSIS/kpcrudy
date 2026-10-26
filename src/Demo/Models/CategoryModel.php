<?php

namespace Kpcrudy\Demo\Models;

use CodeIgniter\Model;

/**
 * Used by Admin\Categories to show the "model mode" of kpcrudy: the CRUD
 * reuses the table, primary key, allowed fields, timestamps and the
 * validation rules defined here.
 */
class CategoryModel extends Model
{
    protected $table         = 'kpdemo_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $allowedFields = ['name', 'color', 'description'];
    protected $useTimestamps = true;

    // The primary key needs its own rule to use {id} in the other rules (CodeIgniter 4.5+)
    protected $validationRules = [
        'id'    => 'permit_empty|is_natural_no_zero',
        'name'  => 'required|min_length[3]|max_length[80]|is_unique[kpdemo_categories.name,id,{id}]',
        'color' => 'required|regex_match[/^#[0-9a-fA-F]{6}$/]',
    ];

    protected $validationMessages = [
        'name'  => ['is_unique' => 'There is already a category with this name.'],
        'color' => ['regex_match' => 'The colour must use the #rrggbb format.'],
    ];
}
