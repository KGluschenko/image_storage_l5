<?php
return array(
    'title' => "Теги",

    'per_page' => 20,

    //Only text\textarea\checkbox\datetime fields are supported for now
    'fields' => array(
        'title' => array(
            'caption' => 'Название',
            'type' => 'text',
            'field' => 'string',
            'tabs' => config('translations.config.languages')
        ),
        'is_active' => array(
            'caption' => 'Тег активен',
            'type' => 'checkbox',
            'options' => array(
                1 => 'Активные',
                0 => 'He aктивные',
            ),
            'field' => 'tinyInteger',
        ),
    ),

);
