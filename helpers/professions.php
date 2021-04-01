<?php

function get_professions($professions_type, $lang)
{
    $professions_data = pods(
        $professions_type,
        [
            'limit' => -1,
            'orderby' => 't.name ASC',
            'select' => implode(', ', [
                't.id',
                $lang === 'cs' ? 't.namecz AS name' : 't.name AS name',
                't.palladio',
            ]),
        ]
    );

    $professions = [];

    while ($professions_data->fetch()) {
        $professions[] = [
            'id' => $professions_data->display('id'),
            'name' => $professions_data->display('name'),
            'palladio' => $professions_data->field('palladio') == 0 ? false : true,
        ];
    }

    return $professions;
}
