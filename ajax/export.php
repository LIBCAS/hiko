<?php

ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);

require_once get_template_directory() . '/vendor/autoload.php';


use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

function export_to_palladio($type)
{
    /*
    * needed data:
    *
    * author: First name (A); Last name (A); Gender (A); Nationality (A); Age (A); Profession (A);
    * recipient: First name (R); Last name (R); Gender (RA); Nationality (R); Age (R); Profession (R);
    * letter: Date of dispatch; Place of dispatch; Place of dispatch (coordinates); Place of arrival; Place of arrival (coordinates); Languages; Keywords
    */

    $letters = get_hiko_post_types($type)['letter'];

    $fields = [
        't.ID',
        't.date_day',
        't.date_month',
        't.date_year',
        't.languages',
        't.name',
        'l_author.surname AS a_surname',
        'l_author.forename AS a_forename',
        'l_author.birth_year AS a_birth_year',
        'l_author.profession AS a_profession',
        'l_author.nationality AS a_nationality',
        'l_author.gender AS a_gender',
        'recipient.surname AS r_surname',
        'recipient.forename AS r_forename',
        'recipient.birth_year AS r_birth_year',
        'recipient.profession AS r_profession',
        'recipient.nationality AS r_nationality',
        'recipient.gender AS r_gender',
        'origin.name AS o_name',
        'origin.longitude AS o_longitude',
        'origin.latitude AS o_latitude',
        'dest.name AS d_name',
        'dest.longitude AS d_longitude',
        'dest.latitude AS d_latitude',
        'keywords.name AS keyword',
    ];

    $fields = implode(', ', $fields);

    $pods = pods(
        $letters,
        [
            'groupby' => 't.id',
            'limit' => -1,
            'orderby' => 't.name ASC',
            'select' => $fields,
        ]
    );

    echo '<hr>';
    var_dump(str_replace('@wp_podsrel', 'hka_podsrel', $pods->sql));
    echo '<hr>';

    while ($pods->fetch()) {
        //var_dump($pods);
    }
}
