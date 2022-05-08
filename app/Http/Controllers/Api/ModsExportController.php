<?php

namespace App\Http\Controllers\Api;

use App\Models\Letter;
use App\Http\Controllers\Controller;

class ModsExportController extends Controller
{
    public function __invoke()
    {
        $result = '<?xml version="1.0" encoding="UTF-8"?><mods_records>';

        Letter::where('status', '=', 'publish')
            ->with('identities', 'places', 'keywords')
            ->get()
            ->map(function ($letter) {
                $letter['identities_grouped'] = $letter->identities->groupBy('pivot.role')->toArray();
                $letter['places_grouped'] = $letter->places->groupBy('pivot.role')->toArray();
                return $letter;
            })
            ->each(function ($letter) use (&$result) {
                $result .= $this->createRecord($letter);
            });

        $result .= '</mods_records>';

        return response($result, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    protected function createRecord(Letter $letter)
    {
        //$record = '<mods version="3.7" xsi:schemaLocation="http://www.loc.gov/mods/v3 https://www.loc.gov/standards/mods/v3/mods-3-7.xsd">';
        $record = '<mods version="3.7">';
        $record .= "<titleInfo><title>{$letter->name}</title></titleInfo>";
        $record .= $this->dateCreated($letter);
        $record .= $this->notes($letter);
        $record .= $this->identity($letter, 'author');
        $record .= $this->identity($letter, 'recipient');
        $record .= $this->place($letter, 'origin');
        $record .= $this->place($letter, 'destination');


        return "{$record}</mods>";
    }

    protected function dateCreated($letter)
    {
        $dateCreatedStart = $this->formatDate($letter->date_day, $letter->date_month, $letter->date_year);
        $dateCreatedEnd = $letter->date_is_range ? $this->formatDate($letter->date_day, $letter->date_month, $letter->date_year) : null;

        $qualifiers = array_filter([
            $letter->date_uncertain ? 'questionable' : null,
            $letter->date_inferred ? 'inferred' : null,
            $letter->date_approximate ? 'approximate' : null,
        ]);

        $qualifiers = 'qualifier="' . implode(' ', $qualifiers) . '"';


        if ($dateCreatedStart && $dateCreatedEnd) {
            $result = "<dateCreated {$qualifiers}>{$dateCreatedStart}</dateCreated>";
            $result .= "<dateCreated _attributes=\"start\">{$dateCreatedStart}</dateCreated>";
            $result .= "<dateCreated _attributes=\"end\">{$dateCreatedEnd}</dateCreated>";
            return $result;
        }

        return "<dateCreated {$qualifiers}>{$dateCreatedStart}</dateCreated>";
    }

    protected function formatDate($day, $month, $year)
    {
        if (!$day && !$month && !$year) {
            return null;
        }

        $date = $day ? "{$day}." : '';
        $date .= $month ? "{$month}." : '';
        $date .= $year ? "{$year}" : '';

        return $date;
    }

    protected function notes($letter)
    {
        $notes = '';

        $types = [
            'date_note' => 'date',
            'author_note' => 'statement of responsibility',
            'recipient_note' => 'recipient',
            'destination_note' => 'destination',
            'origin_note' => 'origin',
            'people_mentioned_note' => 'people mentioned',
            'notes_public' => '',
        ];

        foreach ($types as $key => $type) {
            if ($letter->{$key}) {
                $note = str_replace('"', "'", $letter->{$key});
                $notes .= "<note type=\"{$type}\">{$note}</note>";
            }
        }

        return $notes;
    }

    protected function identity($letter, $type)
    {
        if (!isset($letter->identities_grouped[$type])) {
            return '';
        }

        $qualifiers = array_filter([
            $letter->{$type . '_inferred'} ? 'inferred' : null,
            $letter->{$type . '_uncertain'} ? 'questionable' : null,
        ]);

        $qualifiers = 'qualifier="' . implode(' ', $qualifiers) . '"';

        $identities = '';

        foreach ($letter->identities_grouped[$type] as $identity) {
            $identities .= "<name {$qualifiers} type=\"";
            $identities .= $identity['type'] === 'institution' ? 'corporate' : 'personal';
            $identities .= '">';
            $identities .= '<namePart>' . str_replace('"', "'", $identity['name']) . '</namePart>';
            $identities .= $identity['pivot']['marked']
                ? '<displayForm>' . str_replace('"', "'", $identity['pivot']['marked']) . '</displayForm>'
                : '';
            $identities .= $identity['birth_year'] || $identity['death_year']
                ? "<namePart type=\"date\">{$identity['birth_year']}-{$identity['death_year']}</namePart>"
                : '';
            $identities .= '<role><roleTerm type="text">' . $type . '</roleTerm></role>';
            $identities .= $identity['pivot']['salutation']
                ? '<salut>' . str_replace('"', "'", $identity['pivot']['salutation']) . '</salut>'
                : '';
            $identities .= $identity['viaf_id']
                ? '<nameIdentifier>' . str_replace('"', "'", $identity['viaf_id']) . '</nameIdentifier>'
                : '';

            $identities .= '</name>';
        }

        return $identities;
    }

    protected function place($letter, $type)
    {
        if (!isset($letter->places_grouped[$type])) {
            return '';
        }

        $qualifiers = array_filter([
            $letter->{$type . '_inferred'} ? 'inferred' : null,
            $letter->{$type . '_uncertain'} ? 'questionable' : null,
        ]);

        $qualifiers = 'qualifier="' . implode(' ', $qualifiers) . '"';

        $places = '';

        foreach ($letter->places_grouped[$type] as $place) {
            $places .= "<originInfo {$qualifiers} type=\"{$type}\">";
            $places .= '<place><placeTerm>' . str_replace('"', "'", $place['name']) . '</placeTerm>';
            $places .= $place['pivot']['marked']
                ? '<placeTerm type="display">' . str_replace('"', "'", $place['pivot']['marked']) . '</placeTerm>'
                : '';
            $places .= $place['geoname_id']
                ? '<placeTerm type="authority">' . str_replace('"', "'", $place['geoname_id']) . '</placeTerm>'
                : '';
            $places .= '</place>';
            $places .= '</originInfo>';
        }

        return $places;
    }
}
