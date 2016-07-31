<?php
// WARNING! THIS IS A GENERATED FILE, PLEASE DO NOT EDIT!

namespace gen\Entities;


use Nuclear\Hierarchy\NodeSourceExtension;

class NsProjecttest extends NodeSourceExtension {

    /**
     * The fillable fields for the model.
     */
    protected $fillable = ['date', 'area', 'location'];

    /**
     * Returns the fields for the model
     */
    public static function getSourceFields()
    {
        return ['date', 'area', 'location'];
    }

    /**
     * Returns searchables for the model
     */
    public static function getSearchableFields()
    {
        return [
            'columns' => ['ns_projecttests.location' => 10],
            'joins' => [
                'ns_projecttests' => ['nodes.id', 'ns_projecttests.node_id'],
            ]
        ];
    }

}